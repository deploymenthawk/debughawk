<?php

namespace DebugHawk\Collectors;

use DebugHawk\NeedsInitiatingInterface;

class RequestCollector extends Collector implements NeedsInitiatingInterface {
	public string $key = 'request';

	public ?int $http_status_code = null;

	public ?string $redirect_location = null;

	public function init(): void {
		add_filter( 'wp_redirect', [ $this, 'capture_redirect' ], 9999 );
		add_filter( 'wp_redirect_status', [ $this, 'capture_redirect_status' ], 9999 );
	}

	public function gather(): array {
		$scheme = is_ssl() ? 'https' : 'http';

		$host        = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( $_SERVER['HTTP_HOST'] ) : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '';
		$method      = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) : '';

		return array_filter( [
			'url'               => sprintf( '%s://%s%s', $scheme, $host, $request_uri ),
			'method'            => $method,
			'status'            => $this->http_status_code ?? http_response_code(),
			'redirect_location' => $this->redirect_location,
			'identifier'        => uniqid(),
			'timestamp_ms'      => date_create()->format( 'Uv' ),
		] );
	}

	public function capture_redirect( $location ) {
		$this->redirect_location = $location;

		return $location;
	}

	public function capture_redirect_status( $status ) {
		$this->http_status_code = $status;

		return $status;
	}

	public function is_redirect(): bool {
		return is_int( $this->http_status_code ) && $this->http_status_code >= 300 && $this->http_status_code < 400;
	}
}