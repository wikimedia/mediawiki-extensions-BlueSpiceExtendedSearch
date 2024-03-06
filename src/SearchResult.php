<?php

namespace BS\ExtendedSearch;

class SearchResult {
	/**
	 * @var array
	 */
	private $data;

	/**
	 * @param array $data
	 * @param string $type
	 */
	public function __construct( array $data, string $type ) {
		$this->data = $data;
		$this->data['type'] = $type;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->data['_id'];
	}

	/**
	 * @return float|null
	 */
	public function getScore(): ?float {
		return $this->data['_score'];
	}

	/**
	 * @return string
	 */
	public function getIndex(): string {
		return $this->data['_index'];
	}

	/**
	 * @return array
	 */
	public function getData(): array {
		return $this->data['_source'] ?? [];
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->data['type'];
	}

	/**
	 * @return array
	 */
	public function getSort(): array {
		return $this->data['sort'] ?? [];
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function setParam( $name, $value ) {
		$this->data[$name] = $value;
	}

	/**
	 * @param string $param
	 *
	 * @return mixed|null
	 */
	public function getParam( string $param ) {
		return $this->data[$param] ?? null;
	}

	/**
	 * @param string $param
	 *
	 * @return mixed|null
	 */
	public function getSourceParam( string $param ) {
		return $this->getData()[$param] ?? null;
	}
}
