<?php

namespace DebugHawk;

/**
 * @property boolean $enabled
 * @property string $endpoint
 * @property float $sample_rate
 * @property string $secret
 * @property boolean $trace_admin_pages
 * @property int $slow_queries_threshold
 * @property int $slow_queries_limit
 */
class Config {
	private const DEFAULT_CONFIG = [
		'enabled'                => true,
		'sample_rate'            => 1,
		'trace_admin_pages'      => true,
		'slow_queries_threshold' => 50, // 50 ms
		'slow_queries_limit'     => 3,
	];

	private array $config;
	public string $path;
	public string $url;
	public string $version;

	public function __construct( array $config, string $path, string $version ) {
		$this->config  = $config;
		$this->path    = $path;
		$this->url     = plugin_dir_url( $path );
		$this->version = $version;
	}

	public function configured(): bool {
		return is_string( $this->endpoint ) && is_string( $this->secret );
	}

	public function __get( string $name ) {
		if ( array_key_exists( $name, $this->config ) ) {
			return $this->config[ $name ];
		}

		if ( array_key_exists( $name, self::DEFAULT_CONFIG ) ) {
			return self::DEFAULT_CONFIG[ $name ];
		}

		return null;
	}
}