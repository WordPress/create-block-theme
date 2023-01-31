<?php

namespace VariableAnalysis\Lib;

/**
 * Holds details of a for loop.
 */
class ForLoopInfo
{
	/**
	 * The position of the `for` token.
	 *
	 * @var int
	 */
	public $forIndex;

	/**
	 * The position of the initialization expression opener for the loop.
	 *
	 * @var int
	 */
	public $initStart;

	/**
	 * The position of the initialization expression closer for the loop.
	 *
	 * @var int
	 */
	public $initEnd;

	/**
	 * The position of the condition expression opener for the loop.
	 *
	 * @var int
	 */
	public $conditionStart;

	/**
	 * The position of the condition expression closer for the loop.
	 *
	 * @var int
	 */
	public $conditionEnd;

	/**
	 * The position of the increment expression opener for the loop.
	 *
	 * @var int
	 */
	public $incrementStart;

	/**
	 * The position of the increment expression closer for the loop.
	 *
	 * @var int
	 */
	public $incrementEnd;

	/**
	 * The position of the block opener for the loop.
	 *
	 * @var int
	 */
	public $blockStart;

	/**
	 * The position of the block closer for the loop.
	 *
	 * @var int
	 */
	public $blockEnd;

	/**
	 * Any variables defined inside the third expression of the loop.
	 *
	 * The key is the variable index.
	 *
	 * @var array<int, \VariableAnalysis\Lib\VariableInfo>
	 */
	public $incrementVariables = [];

	/**
	 * @param int $forIndex
	 * @param int $blockStart
	 * @param int $blockEnd
	 * @param int $initStart
	 * @param int $initEnd
	 * @param int $conditionStart
	 * @param int $conditionEnd
	 * @param int $incrementStart
	 * @param int $incrementEnd
	 */
	public function __construct(
		$forIndex,
		$blockStart,
		$blockEnd,
		$initStart,
		$initEnd,
		$conditionStart,
		$conditionEnd,
		$incrementStart,
		$incrementEnd
	) {
		$this->forIndex = $forIndex;
		$this->blockStart = $blockStart;
		$this->blockEnd = $blockEnd;
		$this->initStart = $initStart;
		$this->initEnd = $initEnd;
		$this->conditionStart = $conditionStart;
		$this->conditionEnd = $conditionEnd;
		$this->incrementStart = $incrementStart;
		$this->incrementEnd = $incrementEnd;
	}
}
