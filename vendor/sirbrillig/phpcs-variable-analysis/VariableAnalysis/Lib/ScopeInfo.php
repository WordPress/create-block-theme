<?php

namespace VariableAnalysis\Lib;

/**
 * Holds details of a scope.
 */
class ScopeInfo
{
	/**
	 * The token index of the start of this scope.
	 *
	 * @var int
	 */
	public $scopeStartIndex;

	/**
	 * The token index of the end of this scope, if important.
	 *
	 * @var int|null
	 */
	public $scopeEndIndex;

	/**
	 * The variables defined in this scope.
	 *
	 * @var VariableInfo[]
	 */
	public $variables = [];

	/**
	 * @param int      $scopeStartIndex
	 * @param int|null $scopeEndIndex
	 */
	public function __construct($scopeStartIndex, $scopeEndIndex = null)
	{
		$this->scopeStartIndex = $scopeStartIndex;
		$this->scopeEndIndex = $scopeEndIndex;
	}
}
