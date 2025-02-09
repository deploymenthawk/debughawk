<?php

namespace DebugHawk\Collectors;

use DebugHawk\Config;
use DebugHawk\Util;

class DatabaseCollector implements CollectorInterface {
	protected Config $config;
	protected ?float $execution_time = null;
	protected ?array $slow_queries = null;
	protected ?array $query_types = null;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public function collect(): array {
		global $wpdb;

		$this->parse_queries();

		return [
			'duration_ms'      => $this->execution_time,
			'query_count'      => $wpdb->num_queries ?? null,
			'slow_query_count' => is_array( $this->slow_queries )
				? count( $this->slow_queries )
				: null,
			'slow_queries'     => is_array( $this->slow_queries )
				? array_slice( $this->slow_queries, 0, $this->config->slow_queries_limit )
				: null,
			'query_types'      => $this->query_types,
		];
	}

	protected function parse_queries(): void {
		global $wpdb;

		if ( $wpdb->queries ) {
			$execution_time = 0;
			$slow_queries   = [];

			$this->query_types = [];

			foreach ( $wpdb->queries as $query ) {
				$query_time     = Util::seconds_to_milliseconds( $query[1] );
				$execution_time += $query_time;

				$query_type = $this->determine_query_type( $query[0] );
				$this->count_query_type( $query_type );

				if ( $query_time > $this->config->slow_queries_threshold ) {
					$slow_queries[] = [
						'sql' => strlen( $query[0] ) > 256
							? substr( $query[0], 0, 256 )
							: $query[0],
						'duration_ms' => $query_time,
						'type'        => $query_type,
					];
				}
			}

			usort( $slow_queries, static function ( $a, $b ) {
				return $b['duration_ms'] <=> $a['duration_ms'];
			} );

			$this->execution_time = $execution_time;
			$this->slow_queries = $slow_queries;
		}
	}

	protected function determine_query_type( string $sql ): string {
		if ( preg_match( '/^\s*([A-Za-z]+)\b/', $sql, $matches ) ) {
			return strtoupper( $matches[1] );
		}

		return 'OTHER';
	}

	protected function count_query_type( $type ): void {
		if ( ! isset( $this->query_types[ $type ] ) ) {
			$this->query_types[ $type ] = 0;
		}

		$this->query_types[ $type ] ++;
	}
}