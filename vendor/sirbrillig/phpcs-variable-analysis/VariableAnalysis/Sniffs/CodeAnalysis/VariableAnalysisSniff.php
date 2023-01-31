<?php

namespace VariableAnalysis\Sniffs\CodeAnalysis;

use VariableAnalysis\Lib\ScopeInfo;
use VariableAnalysis\Lib\ScopeType;
use VariableAnalysis\Lib\VariableInfo;
use VariableAnalysis\Lib\Constants;
use VariableAnalysis\Lib\Helpers;
use VariableAnalysis\Lib\ScopeManager;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

class VariableAnalysisSniff implements Sniff
{
	/**
	 * The current phpcsFile being checked.
	 *
	 * @var File|null phpcsFile
	 */
	protected $currentFile = null;

	/**
	 * @var ScopeManager
	 */
	private $scopeManager;

	/**
	 * A list of for loops, keyed by the index of their first token in this file.
	 *
	 * @var array<int, \VariableAnalysis\Lib\ForLoopInfo>
	 */
	private $forLoops = [];

	/**
	 * A list of custom functions which pass in variables to be initialized by
	 * reference (eg `preg_match()`) and therefore should not require those
	 * variables to be defined ahead of time. The list is space separated and
	 * each entry is of the form `functionName:1,2`. The function name comes
	 * first followed by a colon and a comma-separated list of argument numbers
	 * (starting from 1) which should be considered variable definitions. The
	 * special value `...` in the arguments list will cause all arguments after
	 * the last number to be considered variable definitions.
	 *
	 * @var string|null
	 */
	public $sitePassByRefFunctions = null;

	/**
	 * If set, allows common WordPress pass-by-reference functions in addition to
	 * the standard PHP ones.
	 *
	 * @var bool
	 */
	public $allowWordPressPassByRefFunctions = false;

	/**
	 *  Allow exceptions in a catch block to be unused without warning.
	 *
	 *  @var bool
	 */
	public $allowUnusedCaughtExceptions = true;

	/**
	 *  Allow function parameters to be unused without provoking unused-var warning.
	 *
	 *  @var bool
	 */
	public $allowUnusedFunctionParameters = false;

	/**
	 *  If set, ignores undefined variables in the file scope (the top-level
	 *  scope of a file).
	 *
	 *  @var bool
	 */
	public $allowUndefinedVariablesInFileScope = false;

	/**
	 *  If set, ignores unused variables in the file scope (the top-level
	 *  scope of a file).
	 *
	 *  @var bool
	 */
	public $allowUnusedVariablesInFileScope = false;

	/**
	 *  A space-separated list of names of placeholder variables that you want to
	 *  ignore from unused variable warnings. For example, to ignore the variables
	 *  `$junk` and `$unused`, this could be set to `'junk unused'`.
	 *
	 *  @var string|null
	 */
	public $validUnusedVariableNames = null;

	/**
	 *  A PHP regexp string for variables that you want to ignore from unused
	 *  variable warnings. For example, to ignore the variables `$_junk` and
	 *  `$_unused`, this could be set to `'/^_/'`.
	 *
	 *  @var string|null
	 */
	public $ignoreUnusedRegexp = null;

	/**
	 *  A space-separated list of names of placeholder variables that you want to
	 *  ignore from undefined variable warnings. For example, to ignore the variables
	 *  `$post` and `$undefined`, this could be set to `'post undefined'`.
	 *
	 *  @var string|null
	 */
	public $validUndefinedVariableNames = null;

	/**
	 *  A PHP regexp string for variables that you want to ignore from undefined
	 *  variable warnings. For example, to ignore the variables `$_junk` and
	 *  `$_unused`, this could be set to `'/^_/'`.
	 *
	 *  @var string|null
	 */
	public $validUndefinedVariableRegexp = null;

	/**
	 * Allows unused arguments in a function definition if they are
	 * followed by an argument which is used.
	 *
	 *  @var bool
	 */
	public $allowUnusedParametersBeforeUsed = true;

	/**
	 * If set to true, unused values from the `key => value` syntax
	 * in a `foreach` loop will never be marked as unused.
	 *
	 *  @var bool
	 */
	public $allowUnusedForeachVariables = true;

	/**
	 * If set to true, unused variables in a function before a require or import
	 * statement will not be marked as unused because they may be used in the
	 * required file.
	 *
	 *  @var bool
	 */
	public $allowUnusedVariablesBeforeRequire = false;

	public function __construct()
	{
		$this->scopeManager = new ScopeManager();
	}

	/**
	 * Decide which tokens to scan.
	 *
	 * @return (int|string)[]
	 */
	public function register()
	{
		$types = [
			T_VARIABLE,
			T_DOUBLE_QUOTED_STRING,
			T_HEREDOC,
			T_CLOSE_CURLY_BRACKET,
			T_FUNCTION,
			T_CLOSURE,
			T_STRING,
			T_COMMA,
			T_SEMICOLON,
			T_CLOSE_PARENTHESIS,
			T_FOR,
			T_ENDFOR,
		];
		if (defined('T_FN')) {
			$types[] = T_FN;
		}
		return $types;
	}

	/**
	 * @param string $functionName
	 *
	 * @return array<int|string>
	 */
	private function getPassByReferenceFunction($functionName)
	{
		$passByRefFunctions = Constants::getPassByReferenceFunctions();
		if (!empty($this->sitePassByRefFunctions)) {
			$lines = Helpers::splitStringToArray('/\s+/', trim($this->sitePassByRefFunctions));
			foreach ($lines as $line) {
				list ($function, $args) = explode(':', $line);
				$passByRefFunctions[$function] = explode(',', $args);
			}
		}
		if ($this->allowWordPressPassByRefFunctions) {
			$passByRefFunctions = array_merge($passByRefFunctions, Constants::getWordPressPassByReferenceFunctions());
		}
		return isset($passByRefFunctions[$functionName]) ? $passByRefFunctions[$functionName] : [];
	}

	/**
	 * Scan and process a token.
	 *
	 * This is the main processing function of the sniff. Will run on every token
	 * for which `register()` returns true.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$scopeStartTokenTypes = [
			T_FUNCTION,
			T_CLOSURE,
		];

		$token = $tokens[$stackPtr];

		// Cache the current PHPCS File in an instance variable so it can be more
		// easily accessed in other places which aren't passed the object.
		if ($this->currentFile !== $phpcsFile) {
			$this->currentFile = $phpcsFile;
			$this->forLoops = [];
		}

		// Add the global scope for the current file to our scope indexes.
		$scopesForFilename = $this->scopeManager->getScopesForFilename($phpcsFile->getFilename());
		if (empty($scopesForFilename)) {
			$this->scopeManager->recordScopeStartAndEnd($phpcsFile, 0);
		}

		// Report variables defined but not used in the current scope as unused
		// variables if the current token closes scopes.
		$this->searchForAndProcessClosingScopesAt($phpcsFile, $stackPtr);

		// Scan variables that were postponed because they exist in the increment
		// expression of a for loop if the current token closes a loop.
		$this->processClosingForLoopsAt($phpcsFile, $stackPtr);

		// Find and process variables to perform two jobs: to record variable
		// definition or use, and to report variables as undefined if they are used
		// without having been first defined.
		if ($token['code'] === T_VARIABLE) {
			$this->processVariable($phpcsFile, $stackPtr);
			return;
		}
		if (($token['code'] === T_DOUBLE_QUOTED_STRING) || ($token['code'] === T_HEREDOC)) {
			$this->processVariableInString($phpcsFile, $stackPtr);
			return;
		}
		if (($token['code'] === T_STRING) && ($token['content'] === 'compact')) {
			$this->processCompact($phpcsFile, $stackPtr);
			return;
		}

		// Record for loop boundaries so we can delay scanning the third for loop
		// expression until after the loop has been scanned.
		if ($token['code'] === T_FOR) {
			$this->recordForLoop($phpcsFile, $stackPtr);
			return;
		}

		// If the current token is a call to `get_defined_vars()`, consider that a
		// usage of all variables in the current scope.
		if ($this->isGetDefinedVars($phpcsFile, $stackPtr)) {
			Helpers::debug('get_defined_vars is being called');
			$this->markAllVariablesRead($phpcsFile, $stackPtr);
			return;
		}

		// If the current token starts a scope, record that scope's start and end
		// indexes so that we can determine if variables in that scope are defined
		// and/or used.
		if (
			in_array($token['code'], $scopeStartTokenTypes, true) ||
			Helpers::isArrowFunction($phpcsFile, $stackPtr)
		) {
			Helpers::debug('found scope condition', $token);
			$this->scopeManager->recordScopeStartAndEnd($phpcsFile, $stackPtr);
			return;
		}
	}

	/**
	 * Record the boundaries of a for loop.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return void
	 */
	private function recordForLoop($phpcsFile, $stackPtr)
	{
		$this->forLoops[$stackPtr] = Helpers::makeForLoopInfo($phpcsFile, $stackPtr);
	}

	/**
	 * Find scopes closed by a token and process their variables.
	 *
	 * Calls `processScopeClose()` for each closed scope.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return void
	 */
	private function searchForAndProcessClosingScopesAt($phpcsFile, $stackPtr)
	{
		$scopeIndicesThisCloses = $this->scopeManager->getScopesForScopeEnd($phpcsFile->getFilename(), $stackPtr);

		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		$line = $token['line'];
		foreach ($scopeIndicesThisCloses as $scopeIndexThisCloses) {
			Helpers::debug('found closing scope at index', $stackPtr, 'line', $line, 'for scopes starting at:', $scopeIndexThisCloses->scopeStartIndex);
			$this->processScopeClose($phpcsFile, $scopeIndexThisCloses->scopeStartIndex);
		}
	}

	/**
	 * Scan variables that were postponed because they exist in the increment expression of a for loop.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return void
	 */
	private function processClosingForLoopsAt($phpcsFile, $stackPtr)
	{
		$forLoopsThisCloses = [];
		foreach ($this->forLoops as $forLoop) {
			if ($forLoop->blockEnd === $stackPtr) {
				$forLoopsThisCloses[] = $forLoop;
			}
		}

		foreach ($forLoopsThisCloses as $forLoop) {
			foreach ($forLoop->incrementVariables as $varIndex => $varInfo) {
				Helpers::debug('processing delayed for loop increment variable at', $varIndex, $varInfo);
				$this->processVariable($phpcsFile, $varIndex, ['ignore-for-loops' => true]);
			}
		}
	}

	/**
	 * Return true if the token is a call to `get_defined_vars()`.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	protected function isGetDefinedVars(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		if (! $token || $token['content'] !== 'get_defined_vars') {
			return false;
		}
		// Make sure this is a function call
		$parenPointer = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
		if (! $parenPointer || $tokens[$parenPointer]['code'] !== T_OPEN_PARENTHESIS) {
			return false;
		}
		return true;
	}

	/**
	 * @return string
	 */
	protected function getFilename()
	{
		return $this->currentFile ? $this->currentFile->getFilename() : 'unknown file';
	}

	/**
	 * @param int $currScope
	 *
	 * @return ScopeInfo
	 */
	protected function getOrCreateScopeInfo($currScope)
	{
		$scope = $this->scopeManager->getScopeForScopeStart($this->getFilename(), $currScope);
		if (! $scope) {
			if (! $this->currentFile) {
				throw new \Exception('Cannot create scope info; current file is not set.');
			}
			$scope = $this->scopeManager->recordScopeStartAndEnd($this->currentFile, $currScope);
		}
		return $scope;
	}

	/**
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return VariableInfo|null
	 */
	protected function getVariableInfo($varName, $currScope)
	{
		$scopeInfo = $this->scopeManager->getScopeForScopeStart($this->getFilename(), $currScope);
		return ($scopeInfo && isset($scopeInfo->variables[$varName])) ? $scopeInfo->variables[$varName] : null;
	}

	/**
	 * Returns variable data for a variable at an index.
	 *
	 * The variable will also be added to the list of variables stored in its
	 * scope so that its use or non-use can be reported when those scopes end by
	 * `processScopeClose()`.
	 *
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return VariableInfo
	 */
	protected function getOrCreateVariableInfo($varName, $currScope)
	{
		Helpers::debug("getOrCreateVariableInfo: starting for '{$varName}'");
		$scopeInfo = $this->getOrCreateScopeInfo($currScope);
		if (isset($scopeInfo->variables[$varName])) {
			Helpers::debug("getOrCreateVariableInfo: found variable for '{$varName}'", $scopeInfo->variables[$varName]);
			return $scopeInfo->variables[$varName];
		}
		Helpers::debug("getOrCreateVariableInfo: creating a new variable for '{$varName}' in scope", $scopeInfo);
		$scopeInfo->variables[$varName] = new VariableInfo($varName);
		$validUnusedVariableNames = (empty($this->validUnusedVariableNames))
		? []
		: Helpers::splitStringToArray('/\s+/', trim($this->validUnusedVariableNames));
		$validUndefinedVariableNames = (empty($this->validUndefinedVariableNames))
		? []
		: Helpers::splitStringToArray('/\s+/', trim($this->validUndefinedVariableNames));
		if (in_array($varName, $validUnusedVariableNames)) {
			$scopeInfo->variables[$varName]->ignoreUnused = true;
		}
		if (isset($this->ignoreUnusedRegexp) && preg_match($this->ignoreUnusedRegexp, $varName) === 1) {
			$scopeInfo->variables[$varName]->ignoreUnused = true;
		}
		if ($scopeInfo->scopeStartIndex === 0 && $this->allowUndefinedVariablesInFileScope) {
			$scopeInfo->variables[$varName]->ignoreUndefined = true;
		}
		if (in_array($varName, $validUndefinedVariableNames)) {
			$scopeInfo->variables[$varName]->ignoreUndefined = true;
		}
		if (isset($this->validUndefinedVariableRegexp) && preg_match($this->validUndefinedVariableRegexp, $varName) === 1) {
			$scopeInfo->variables[$varName]->ignoreUndefined = true;
		}
		Helpers::debug("getOrCreateVariableInfo: scope for '{$varName}' is now", $scopeInfo);
		return $scopeInfo->variables[$varName];
	}

	/**
	 * Record that a variable has been defined and assigned a value.
	 *
	 * If a variable has been defined within a scope, it will not be marked as
	 * undefined when that variable is later used. If it is not used, it will be
	 * marked as unused when that scope ends.
	 *
	 * Sometimes it's possible to assign something to a variable without
	 * definining it (eg: assignment to a reference); in that case, use
	 * `markVariableAssignmentWithoutInitialization()`.
	 *
	 * @param string $varName
	 * @param int    $stackPtr
	 * @param int    $currScope
	 *
	 * @return void
	 */
	protected function markVariableAssignment($varName, $stackPtr, $currScope)
	{
		Helpers::debug('markVariableAssignment: starting for', $varName);
		$this->markVariableAssignmentWithoutInitialization($varName, $stackPtr, $currScope);
		Helpers::debug('markVariableAssignment: marked as assigned without initialization', $varName);
		$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
		if (isset($varInfo->firstInitialized) && ($varInfo->firstInitialized <= $stackPtr)) {
			Helpers::debug('markVariableAssignment: variable is already initialized', $varName);
			return;
		}
		$varInfo->firstInitialized = $stackPtr;
		Helpers::debug('markVariableAssignment: marked as initialized', $varName);
	}

	/**
	 * Record that a variable has been assigned a value.
	 *
	 * Does not record that a variable has been defined, which is the usual state
	 * of affairs. For that, use `markVariableAssignment()`.
	 *
	 * This is useful for assignments to references.
	 *
	 * @param string $varName
	 * @param int    $stackPtr
	 * @param int    $currScope
	 *
	 * @return void
	 */
	protected function markVariableAssignmentWithoutInitialization($varName, $stackPtr, $currScope)
	{
		$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);

		// Is the variable referencing another variable? If so, mark that variable used also.
		if ($varInfo->referencedVariableScope !== null && $varInfo->referencedVariableScope !== $currScope) {
			// Don't do this if the referenced variable does not exist; eg: if it's going to be bound at runtime like in array_walk
			if ($this->getVariableInfo($varInfo->name, $varInfo->referencedVariableScope)) {
				Helpers::debug('markVariableAssignmentWithoutInitialization: marking referenced variable as assigned also', $varName);
				$this->markVariableAssignment($varInfo->name, $stackPtr, $varInfo->referencedVariableScope);
			}
		}

		if (empty($varInfo->scopeType)) {
			$varInfo->scopeType = ScopeType::LOCAL;
		}
		$varInfo->allAssignments[] = $stackPtr;
	}

	/**
	 * Record that a variable has been defined within a scope.
	 *
	 * @param string                                                                                           $varName
	 * @param ScopeType::PARAM|ScopeType::BOUND|ScopeType::LOCAL|ScopeType::GLOBALSCOPE|ScopeType::STATICSCOPE $scopeType
	 * @param ?string                                                                                          $typeHint
	 * @param int                                                                                              $stackPtr
	 * @param int                                                                                              $currScope
	 * @param ?bool                                                                                            $permitMatchingRedeclaration
	 *
	 * @return void
	 */
	protected function markVariableDeclaration(
		$varName,
		$scopeType,
		$typeHint,
		$stackPtr,
		$currScope,
		$permitMatchingRedeclaration = false
	) {
		Helpers::debug("marking variable '{$varName}' declared in scope starting at token", $currScope);
		$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);

		if (! empty($varInfo->scopeType)) {
			if (($permitMatchingRedeclaration === false) || ($varInfo->scopeType !== $scopeType)) {
				//  Issue redeclaration/reuse warning
				//  Note: we check off scopeType not firstDeclared, this is so that
				//    we catch declarations that come after implicit declarations like
				//    use of a variable as a local.
				$this->addWarning(
					'Redeclaration of %s %s as %s.',
					$stackPtr,
					'VariableRedeclaration',
					[
						VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
						"\${$varName}",
						VariableInfo::$scopeTypeDescriptions[$scopeType],
					]
				);
			}
		}

		$varInfo->scopeType = $scopeType;
		if (isset($typeHint)) {
			$varInfo->typeHint = $typeHint;
		}
		if (isset($varInfo->firstDeclared) && ($varInfo->firstDeclared <= $stackPtr)) {
			Helpers::debug("variable '{$varName}' was already marked declared", $varInfo);
			return;
		}
		$varInfo->firstDeclared = $stackPtr;
		$varInfo->allAssignments[] = $stackPtr;
		Helpers::debug("variable '{$varName}' marked declared", $varInfo);
	}

	/**
	 * @param string   $message
	 * @param int      $stackPtr
	 * @param string   $code
	 * @param string[] $data
	 *
	 * @return void
	 */
	protected function addWarning($message, $stackPtr, $code, $data)
	{
		if (! $this->currentFile) {
			throw new \Exception('Cannot add warning; current file is not set.');
		}
		$this->currentFile->addWarning(
			$message,
			$stackPtr,
			$code,
			$data
		);
	}

	/**
	 * Record that a variable has been used within a scope.
	 *
	 * If the variable has not been defined first, this will still mark it used.
	 * To display a warning for undefined variables, use
	 * `markVariableReadAndWarnIfUndefined()`.
	 *
	 * @param string $varName
	 * @param int    $stackPtr
	 * @param int    $currScope
	 *
	 * @return void
	 */
	protected function markVariableRead($varName, $stackPtr, $currScope)
	{
		$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
		if (isset($varInfo->firstRead) && ($varInfo->firstRead <= $stackPtr)) {
			return;
		}
		$varInfo->firstRead = $stackPtr;
	}

	/**
	 * Return true if a variable is defined within a scope.
	 *
	 * @param string $varName
	 * @param int    $stackPtr
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function isVariableUndefined($varName, $stackPtr, $currScope)
	{
		$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
		Helpers::debug('isVariableUndefined', $varInfo, 'at', $stackPtr);
		if ($varInfo->ignoreUndefined) {
			return false;
		}
		if (isset($varInfo->firstDeclared) && $varInfo->firstDeclared <= $stackPtr) {
			return false;
		}
		if (isset($varInfo->firstInitialized) && $varInfo->firstInitialized <= $stackPtr) {
			return false;
		}
		// If we are inside a for loop increment expression, check to see if the
		// variable was defined inside the for loop.
		foreach ($this->forLoops as $forLoop) {
			if ($stackPtr > $forLoop->incrementStart && $stackPtr < $forLoop->incrementEnd) {
				Helpers::debug('isVariableUndefined looking at increment expression for loop', $forLoop);
				if (
					isset($varInfo->firstInitialized)
					&& $varInfo->firstInitialized > $forLoop->blockStart
					&& $varInfo->firstInitialized < $forLoop->blockEnd
				) {
					return false;
				}
			}
		}
		// If we are inside a for loop body, check to see if the variable was
		// defined in that loop's third expression.
		foreach ($this->forLoops as $forLoop) {
			if ($stackPtr > $forLoop->blockStart && $stackPtr < $forLoop->blockEnd) {
				foreach ($forLoop->incrementVariables as $forLoopVarInfo) {
					if ($varInfo === $forLoopVarInfo) {
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * Record a variable use and report a warning if the variable is undefined.
	 *
	 * @param File   $phpcsFile
	 * @param string $varName
	 * @param int    $stackPtr
	 * @param int    $currScope
	 *
	 * @return void
	 */
	protected function markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope)
	{
		$this->markVariableRead($varName, $stackPtr, $currScope);
		if ($this->isVariableUndefined($varName, $stackPtr, $currScope) === true) {
			Helpers::debug("variable $varName looks undefined");

			if (Helpers::isVariableArrayPushShortcut($phpcsFile, $stackPtr)) {
				$this->warnAboutUndefinedArrayPushShortcut($phpcsFile, $varName, $stackPtr);
				// Mark the variable as defined if it's of the form `$x[] = 1;`
				$this->markVariableAssignment($varName, $stackPtr, $currScope);
				return;
			}

			if (Helpers::isVariableInsideUnset($phpcsFile, $stackPtr)) {
				$this->warnAboutUndefinedUnset($phpcsFile, $varName, $stackPtr);
				return;
			}

			$this->warnAboutUndefinedVariable($phpcsFile, $varName, $stackPtr);
		}
	}

	/**
	 * Mark all variables within a scope as being used.
	 *
	 * This will prevent any of the variables in that scope from being reported
	 * as unused.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return void
	 */
	protected function markAllVariablesRead(File $phpcsFile, $stackPtr)
	{
		$currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);
		if ($currScope === null) {
			return;
		}
		$scopeInfo = $this->getOrCreateScopeInfo($currScope);
		$count = count($scopeInfo->variables);
		Helpers::debug("marking all $count variables in scope as read");
		foreach ($scopeInfo->variables as $varInfo) {
			$this->markVariableRead($varInfo->name, $stackPtr, $scopeInfo->scopeStartIndex);
		}
	}

	/**
	 * Process a parameter definition if it is inside a function definition.
	 *
	 * This does not include variables imported by a "use" statement.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $outerScope
	 *
	 * @return void
	 */
	protected function processVariableAsFunctionParameter(File $phpcsFile, $stackPtr, $varName, $outerScope)
	{
		Helpers::debug('processVariableAsFunctionParameter', $stackPtr, $varName);
		$tokens = $phpcsFile->getTokens();

		$functionPtr = Helpers::getFunctionIndexForFunctionParameter($phpcsFile, $stackPtr);
		if (! is_int($functionPtr)) {
			throw new \Exception("Function index not found for function argument index {$stackPtr}");
		}

		Helpers::debug('processVariableAsFunctionParameter found function definition', $tokens[$functionPtr]);
		$this->markVariableDeclaration($varName, ScopeType::PARAM, null, $stackPtr, $functionPtr);

		// Are we pass-by-reference?
		$referencePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true);
		if (($referencePtr !== false) && ($tokens[$referencePtr]['code'] === T_BITWISE_AND)) {
			Helpers::debug('processVariableAsFunctionParameter found pass-by-reference to scope', $outerScope);
			$varInfo = $this->getOrCreateVariableInfo($varName, $functionPtr);
			$varInfo->referencedVariableScope = $outerScope;
		}

		//  Are we optional with a default?
		if (Helpers::getNextAssignPointer($phpcsFile, $stackPtr) !== null) {
			Helpers::debug('processVariableAsFunctionParameter optional with default');
			$this->markVariableAssignment($varName, $stackPtr, $functionPtr);
		}

		// Are we using constructor promotion? If so, that counts as both definition and use.
		if (Helpers::isConstructorPromotion($phpcsFile, $stackPtr)) {
			Helpers::debug('processVariableAsFunctionParameter constructor promotion');
			$this->markVariableRead($varName, $stackPtr, $outerScope);
		}
	}

	/**
	 * Process a variable definition if it is inside a function's "use" import.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $outerScope The start of the scope outside the function definition
	 *
	 * @return void
	 */
	protected function processVariableAsUseImportDefinition(File $phpcsFile, $stackPtr, $varName, $outerScope)
	{
		$tokens = $phpcsFile->getTokens();

		Helpers::debug('processVariableAsUseImportDefinition', $stackPtr, $varName, $outerScope);

		$endOfArgsPtr = $phpcsFile->findPrevious([T_CLOSE_PARENTHESIS], $stackPtr - 1, null);
		if (! is_int($endOfArgsPtr)) {
			throw new \Exception("Arguments index not found for function use index {$stackPtr} when processing variable {$varName}");
		}
		$functionPtr = Helpers::getFunctionIndexForFunctionParameter($phpcsFile, $endOfArgsPtr);
		if (! is_int($functionPtr)) {
			throw new \Exception("Function index not found for function use index {$stackPtr} (using {$endOfArgsPtr}) when processing variable {$varName}");
		}

		// Use is both a read (in the enclosing scope) and a define (in the function scope)
		$this->markVariableRead($varName, $stackPtr, $outerScope);

		// If it's undefined in the enclosing scope, the use is wrong
		if ($this->isVariableUndefined($varName, $stackPtr, $outerScope) === true) {
			Helpers::debug("variable '{$varName}' in function definition looks undefined in scope", $outerScope);
			$this->warnAboutUndefinedVariable($phpcsFile, $varName, $stackPtr);
			return;
		}

		$this->markVariableDeclaration($varName, ScopeType::BOUND, null, $stackPtr, $functionPtr);
		$this->markVariableAssignment($varName, $stackPtr, $functionPtr);

		// Are we pass-by-reference? If so, then any assignment to the variable in
		// the function scope also should count for the enclosing scope.
		$referencePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true);
		if (is_int($referencePtr) && $tokens[$referencePtr]['code'] === T_BITWISE_AND) {
			Helpers::debug("variable '{$varName}' in function definition looks passed by reference");
			$varInfo = $this->getOrCreateVariableInfo($varName, $functionPtr);
			$varInfo->referencedVariableScope = $outerScope;
		}
	}

	/**
	 * Process a class property that is being defined.
	 *
	 * Property definitions are ignored currently because all property access is
	 * legal, even to undefined properties.
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	protected function processVariableAsClassProperty(File $phpcsFile, $stackPtr)
	{
		$propertyDeclarationKeywords = [
			T_PUBLIC,
			T_PRIVATE,
			T_PROTECTED,
			T_VAR,
		];
		$stopAtPtr = $stackPtr - 2;
		$visibilityPtr = $phpcsFile->findPrevious($propertyDeclarationKeywords, $stackPtr - 1, $stopAtPtr > 0 ? $stopAtPtr : 0);
		if ($visibilityPtr) {
			return true;
		}
		$staticPtr = $phpcsFile->findPrevious(T_STATIC, $stackPtr - 1, $stopAtPtr > 0 ? $stopAtPtr : 0);
		if (! $staticPtr) {
			return false;
		}
		$stopAtPtr = $staticPtr - 2;
		$visibilityPtr = $phpcsFile->findPrevious($propertyDeclarationKeywords, $staticPtr - 1, $stopAtPtr > 0 ? $stopAtPtr : 0);
		if ($visibilityPtr) {
			return true;
		}
		// it's legal to use `static` to define properties as well as to
		// define variables, so make sure we are not in a function before
		// assuming it's a property.
		$tokens = $phpcsFile->getTokens();
		$token  = $tokens[$stackPtr];
		if ($token && !empty($token['conditions']) && !Helpers::areConditionsWithinFunctionBeforeClass($token['conditions'])) {
			return Helpers::areAnyConditionsAClass($token['conditions']);
		}
		return false;
	}

	/**
	 * Process a variable that is being accessed inside a catch block.
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function processVariableAsCatchBlock(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		// Are we a catch block parameter?
		$openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
		if ($openPtr === null) {
			return false;
		}

		$catchPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $openPtr - 1, null, true, null, true);
		if (($catchPtr !== false) && ($tokens[$catchPtr]['code'] === T_CATCH)) {
			// Scope of the exception var is actually the function, not just the catch block.
			$this->markVariableDeclaration($varName, ScopeType::LOCAL, null, $stackPtr, $currScope, true);
			$this->markVariableAssignment($varName, $stackPtr, $currScope);
			if ($this->allowUnusedCaughtExceptions) {
				$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
				$varInfo->ignoreUnused = true;
			}
			return true;
		}
		return false;
	}

	/**
	 * Process a variable that is being accessed as a member of `$this`.
	 *
	 * Looks for variables of the form `$this->myVariable`.
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 *
	 * @return bool
	 */
	protected function processVariableAsThisWithinClass(File $phpcsFile, $stackPtr, $varName)
	{
		$tokens = $phpcsFile->getTokens();
		$token  = $tokens[$stackPtr];

		// Are we $this within a class?
		if (($varName !== 'this') || empty($token['conditions'])) {
			return false;
		}

		$inFunction = false;
		foreach (array_reverse($token['conditions'], true) as $scopeCode) {
			//  $this within a closure is valid
			if ($scopeCode === T_CLOSURE && $inFunction === false) {
				return true;
			}
			if ($scopeCode === T_CLASS || $scopeCode === T_ANON_CLASS || $scopeCode === T_TRAIT) {
				return true;
			}

			// Handle nested function declarations.
			if ($scopeCode === T_FUNCTION) {
				if ($inFunction === true) {
					break;
				}

				$inFunction = true;
			}
		}

		return false;
	}

	/**
	 * Process a superglobal variable that is being accessed.
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param string $varName
	 *
	 * @return bool
	 */
	protected function processVariableAsSuperGlobal($varName)
	{
		$superglobals = [
			'GLOBALS',
			'_SERVER',
			'_GET',
			'_POST',
			'_FILES',
			'_COOKIE',
			'_SESSION',
			'_REQUEST',
			'_ENV',
			'argv',
			'argc',
			'http_response_header',
			'HTTP_RAW_POST_DATA',
		];
		// Are we a superglobal variable?
		return (in_array($varName, $superglobals, true));
	}

	/**
	 * Process a variable that is being accessed with static syntax.
	 *
	 * That is, this will record the use of a variable of the form
	 * `MyClass::$myVariable` or `self::$myVariable`.
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	protected function processVariableAsStaticMember(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$doubleColonPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
		if ($doubleColonPtr === false || $tokens[$doubleColonPtr]['code'] !== T_DOUBLE_COLON) {
			return false;
		}
		$classNamePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $doubleColonPtr - 1, null, true);
		$staticReferences = [
			T_STRING,
			T_SELF,
			T_PARENT,
			T_STATIC,
			T_VARIABLE,
		];
		if ($classNamePtr === false || ! in_array($tokens[$classNamePtr]['code'], $staticReferences, true)) {
			return false;
		}
		// "When calling static methods, the function call is stronger than the
		// static property operator" so look for a function call.
		$parenPointer = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
		if ($parenPointer !== false && $tokens[$parenPointer]['code'] === T_OPEN_PARENTHESIS) {
			return false;
		}
		return true;
	}

	/**
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 *
	 * @return bool
	 */
	protected function processVariableAsStaticOutsideClass(File $phpcsFile, $stackPtr, $varName)
	{
		// Are we refering to self:: outside a class?

		$tokens = $phpcsFile->getTokens();
		$token  = $tokens[$stackPtr];

		$doubleColonPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
		if ($doubleColonPtr === false || $tokens[$doubleColonPtr]['code'] !== T_DOUBLE_COLON) {
			return false;
		}
		$classNamePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $doubleColonPtr - 1, null, true);
		if ($classNamePtr === false) {
			return false;
		}
		$code = $tokens[$classNamePtr]['code'];
		$staticReferences = [
			T_SELF,
			T_STATIC,
		];
		if (! in_array($code, $staticReferences, true)) {
			return false;
		}
		$errorClass = $code === T_SELF ? 'SelfOutsideClass' : 'StaticOutsideClass';
		$staticRefType = $code === T_SELF ? 'self::' : 'static::';
		if (!empty($token['conditions']) && Helpers::areAnyConditionsAClass($token['conditions'])) {
			return false;
		}
		$phpcsFile->addError(
			"Use of {$staticRefType}%s outside class definition.",
			$stackPtr,
			$errorClass,
			["\${$varName}"]
		);
		return true;
	}

	/**
	 * Process a variable that is being assigned.
	 *
	 * This will record that the variable has been defined within a scope so that
	 * later we can determine if it it unused and we can guarantee that any
	 * future uses of the variable are not using an undefined variable.
	 *
	 * References (on either side of an assignment) behave differently and this
	 * function handles those cases as well.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return void
	 */
	protected function processVariableAsAssignment(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		Helpers::debug("processVariableAsAssignment: starting for '{$varName}'");
		$assignPtr = Helpers::getNextAssignPointer($phpcsFile, $stackPtr);
		if (! is_int($assignPtr)) {
			return;
		}

		// If the right-hand-side of the assignment to this variable is a reference
		// variable, then this variable is a reference to that one, and as such any
		// assignment to this variable (except another assignment by reference,
		// which would change the binding) has a side effect of changing the
		// referenced variable and therefore should count as both an assignment and
		// a read.
		$tokens = $phpcsFile->getTokens();
		$referencePtr = $phpcsFile->findNext(Tokens::$emptyTokens, $assignPtr + 1, null, true, null, true);
		if (is_int($referencePtr) && $tokens[$referencePtr]['code'] === T_BITWISE_AND) {
			Helpers::debug('processVariableAsAssignment: found reference variable');
			$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
			// If the variable was already declared, but was not yet read, it is
			// unused because we're about to change the binding; that is, unless we
			// are inside a conditional block because in that case the condition may
			// never activate.
			$scopeInfo = $this->getOrCreateScopeInfo($currScope);
			$ifPtr = Helpers::getClosestIfPositionIfBeforeOtherConditions($tokens[$referencePtr]['conditions']);
			$lastAssignmentPtr = $varInfo->firstDeclared;
			if (! $ifPtr && $lastAssignmentPtr) {
				$this->processScopeCloseForVariable($phpcsFile, $varInfo, $scopeInfo);
			}
			if ($ifPtr && $lastAssignmentPtr && $ifPtr <= $lastAssignmentPtr) {
				$this->processScopeCloseForVariable($phpcsFile, $varInfo, $scopeInfo);
			}
			// The referenced variable may have a different name, but we don't
			// actually need to mark it as used in this case because the act of this
			// assignment will mark it used on the next token.
			$varInfo->referencedVariableScope = $currScope;
			$this->markVariableDeclaration($varName, ScopeType::LOCAL, null, $stackPtr, $currScope, true);
			// An assignment to a reference is a binding and should not count as
			// initialization since it doesn't change any values.
			$this->markVariableAssignmentWithoutInitialization($varName, $stackPtr, $currScope);
			return;
		}

		Helpers::debug('processVariableAsAssignment: marking as assignment in scope', $currScope);
		$this->markVariableAssignment($varName, $stackPtr, $currScope);

		// If the left-hand-side of the assignment (the variable we are examining)
		// is itself a reference, then that counts as a read as well as a write.
		$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
		if ($varInfo->isDynamicReference) {
			Helpers::debug('processVariableAsAssignment: also marking as a use because variable is a reference');
			$this->markVariableRead($varName, $stackPtr, $currScope);
		}
	}

	/**
	 * Processes variables destructured from an array using shorthand list assignment.
	 *
	 * This will record the definition and assignment of variables defined using
	 * the format:
	 *
	 * ```
	 * [ $foo, $bar, $baz ] = $ary;
	 * ```
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function processVariableAsListShorthandAssignment(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		// OK, are we within a [ ... ] construct?
		$openPtr = Helpers::findContainingOpeningSquareBracket($phpcsFile, $stackPtr);
		if (! is_int($openPtr)) {
			return false;
		}

		// OK, we're a [ ... ] construct... are we being assigned to?
		$assignments = Helpers::getListAssignments($phpcsFile, $openPtr);
		if (! $assignments) {
			return false;
		}
		$matchingAssignment = array_reduce($assignments, function ($thisAssignment, $assignment) use ($stackPtr) {
			if ($assignment === $stackPtr) {
				return $assignment;
			}
			return $thisAssignment;
		});
		if (! $matchingAssignment) {
			return false;
		}

		// Yes, we're being assigned.
		$this->markVariableAssignment($varName, $stackPtr, $currScope);
		return true;
	}

	/**
	 * Processes variables destructured from an array using list assignment.
	 *
	 * This will record the definition and assignment of variables defined using
	 * the format:
	 *
	 * ```
	 * list( $foo, $bar, $baz ) = $ary;
	 * ```
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function processVariableAsListAssignment(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		// OK, are we within a list (...) construct?
		$openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
		if ($openPtr === null) {
			return false;
		}

		$prevPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $openPtr - 1, null, true, null, true);
		if ((is_bool($prevPtr)) || ($tokens[$prevPtr]['code'] !== T_LIST)) {
			return false;
		}

		// OK, we're a list (...) construct... are we being assigned to?
		$assignments = Helpers::getListAssignments($phpcsFile, $prevPtr);
		if (! $assignments) {
			return false;
		}
		$matchingAssignment = array_reduce($assignments, function ($thisAssignment, $assignment) use ($stackPtr) {
			if ($assignment === $stackPtr) {
				return $assignment;
			}
			return $thisAssignment;
		});
		if (! $matchingAssignment) {
			return false;
		}

		// Yes, we're being assigned.
		$this->markVariableAssignment($varName, $stackPtr, $currScope);
		return true;
	}

	/**
	 * Process a variable being defined (imported, really) with the `global` keyword.
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function processVariableAsGlobalDeclaration(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		// Are we a global declaration?
		// Search backwards for first token that isn't whitespace/comment, comma or variable.
		$ignore             = Tokens::$emptyTokens;
		$ignore[T_VARIABLE] = T_VARIABLE;
		$ignore[T_COMMA]    = T_COMMA;

		$globalPtr = $phpcsFile->findPrevious($ignore, $stackPtr - 1, null, true, null, true);
		if (($globalPtr === false) || ($tokens[$globalPtr]['code'] !== T_GLOBAL)) {
			return false;
		}

		// It's a global declaration.
		$this->markVariableDeclaration($varName, ScopeType::GLOBALSCOPE, null, $stackPtr, $currScope);
		return true;
	}

	/**
	 * Process a variable as a static declaration within a function.
	 *
	 * Specifically, this looks for variable definitions of the form `static
	 * $foo = 'hello';` or `static int $foo;` inside a function definition.
	 *
	 * This will not operate on variables that are written in a class definition
	 * outside of a function like `static $foo;` or `public static ?int $foo =
	 * 'bar';` because class properties (static or instance) are currently not
	 * tracked by this sniff. This is because a class property might be unused
	 * inside the class, but used outside the class (we cannot easily know if it
	 * is unused); this is also because it's common and legal to define class
	 * properties when they are assigned and that assignment can happen outside a
	 * class (we cannot easily know if the use of a property is undefined). These
	 * sorts of checks are better performed by static analysis tools that can see
	 * a whole project rather than a linter which can only easily see a file or
	 * some lines.
	 *
	 * If found, such a variable will be marked as declared (and possibly
	 * assigned, if it includes an initial value) within the scope of the
	 * function body.
	 *
	 * This will not operate on variables that use late static binding
	 * (`static::$foobar`) or the parameters of static methods even though they
	 * include the word `static` in the same statement.
	 *
	 * This only finds the defintions of static variables. Their use is handled
	 * by `processVariableAsStaticMember()`.
	 *
	 * Can be called for any token and will return false if the variable is not
	 * of this type.
	 *
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function processVariableAsStaticDeclaration(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		// Search backwards for a `static` keyword that occurs before the start of the statement.
		$startOfStatement = $phpcsFile->findPrevious([T_SEMICOLON, T_OPEN_CURLY_BRACKET, T_FN_ARROW, T_OPEN_PARENTHESIS], $stackPtr - 1, null, false, null, true);
		$staticPtr = $phpcsFile->findPrevious([T_STATIC], $stackPtr - 1, null, false, null, true);
		if (! is_int($startOfStatement)) {
			$startOfStatement = 1;
		}
		if (! is_int($staticPtr)) {
			return false;
		}
		// PHPCS is bad at finding the start of statements so we have to do it ourselves.
		if ($staticPtr < $startOfStatement) {
			return false;
		}

		// Is the 'static' keyword an anonymous static function declaration? If so,
		// this is not a static variable declaration.
		$tokenAfterStatic = $phpcsFile->findNext(Tokens::$emptyTokens, $staticPtr + 1, null, true, null, true);
		$functionTokenTypes = [
			T_FUNCTION,
			T_CLOSURE,
			T_FN,
		];
		if (is_int($tokenAfterStatic) && in_array($tokens[$tokenAfterStatic]['code'], $functionTokenTypes, true)) {
			return false;
		}

		// Is the token inside function parameters? If so, this is not a static
		// declaration because we must be inside a function body.
		if (Helpers::isTokenFunctionParameter($phpcsFile, $stackPtr)) {
			return false;
		}

		// Is the token inside a function call? If so, this is not a static
		// declaration.
		if (Helpers::isTokenInsideFunctionCallArgument($phpcsFile, $stackPtr)) {
			return false;
		}

		// Is the keyword a late static binding? If so, this isn't the static
		// keyword we're looking for, but since static:: isn't allowed in a
		// compile-time constant, we also know we can't be part of a static
		// declaration anyway, so there's no need to look any further.
		$lateStaticBindingPtr = $phpcsFile->findNext(T_WHITESPACE, $staticPtr + 1, null, true, null, true);
		if (($lateStaticBindingPtr !== false) && ($tokens[$lateStaticBindingPtr]['code'] === T_DOUBLE_COLON)) {
			return false;
		}

		$this->markVariableDeclaration($varName, ScopeType::STATICSCOPE, null, $stackPtr, $currScope);
		if (Helpers::getNextAssignPointer($phpcsFile, $stackPtr) !== null) {
			$this->markVariableAssignment($varName, $stackPtr, $currScope);
		}
		return true;
	}

	/**
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function processVariableAsForeachLoopVar(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		// Are we a foreach loopvar?
		$openParenPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
		if (! is_int($openParenPtr)) {
			return false;
		}
		$foreachPtr = Helpers::getIntOrNull($phpcsFile->findPrevious(Tokens::$emptyTokens, $openParenPtr - 1, null, true));
		if (! is_int($foreachPtr)) {
			return false;
		}
		if ($tokens[$foreachPtr]['code'] === T_LIST) {
			$openParenPtr = Helpers::findContainingOpeningBracket($phpcsFile, $foreachPtr);
			if (! is_int($openParenPtr)) {
				return false;
			}
			$foreachPtr = Helpers::getIntOrNull($phpcsFile->findPrevious(Tokens::$emptyTokens, $openParenPtr - 1, null, true));
			if (! is_int($foreachPtr)) {
				return false;
			}
		}
		if ($tokens[$foreachPtr]['code'] !== T_FOREACH) {
			return false;
		}

		// Is there an 'as' token between us and the foreach?
		if ($phpcsFile->findPrevious(T_AS, $stackPtr - 1, $openParenPtr) === false) {
			return false;
		}
		$this->markVariableAssignment($varName, $stackPtr, $currScope);
		$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);

		// Is this the value of a key => value foreach?
		if ($phpcsFile->findPrevious(T_DOUBLE_ARROW, $stackPtr - 1, $openParenPtr) !== false) {
			$varInfo->isForeachLoopAssociativeValue = true;
		}

		// Are we pass-by-reference?
		$referencePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true);
		if (($referencePtr !== false) && ($tokens[$referencePtr]['code'] === T_BITWISE_AND)) {
			Helpers::debug('processVariableAsForeachLoopVar: found foreach loop variable assigned by reference');
			$varInfo->isDynamicReference = true;
		}

		return true;
	}

	/**
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function processVariableAsPassByReferenceFunctionCall(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		// Are we pass-by-reference to known pass-by-reference function?
		$functionPtr = Helpers::findFunctionCall($phpcsFile, $stackPtr);
		if ($functionPtr === null || ! isset($tokens[$functionPtr])) {
			return false;
		}

		// Is our function a known pass-by-reference function?
		$functionName = $tokens[$functionPtr]['content'];
		$refArgs = $this->getPassByReferenceFunction($functionName);
		if (! $refArgs) {
			return false;
		}

		$argPtrs = Helpers::findFunctionCallArguments($phpcsFile, $stackPtr);

		// We're within a function call arguments list, find which arg we are.
		$argPos = false;
		foreach ($argPtrs as $idx => $ptrs) {
			if (in_array($stackPtr, $ptrs)) {
				$argPos = $idx + 1;
				break;
			}
		}
		if ($argPos === false) {
			return false;
		}
		if (!in_array($argPos, $refArgs)) {
			// Our arg wasn't mentioned explicitly, are we after an elipsis catch-all?
			$elipsis = array_search('...', $refArgs);
			if ($elipsis === false) {
				return false;
			}
			$elipsis = (int)$elipsis;
			if ($argPos < $refArgs[$elipsis - 1]) {
				return false;
			}
		}

		// Our argument position matches that of a pass-by-ref argument,
		// check that we're the only part of the argument expression.
		foreach ($argPtrs[$argPos - 1] as $ptr) {
			if ($ptr === $stackPtr) {
				continue;
			}
			if (isset(Tokens::$emptyTokens[$tokens[$ptr]['code']]) === false) {
				return false;
			}
		}

		// Just us, we can mark it as a write.
		$this->markVariableAssignment($varName, $stackPtr, $currScope);
		// It's a read as well for purposes of used-variables.
		$this->markVariableRead($varName, $stackPtr, $currScope);
		return true;
	}

	/**
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return bool
	 */
	protected function processVariableAsSymbolicObjectProperty(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		// Are we a symbolic object property/function derefeference?
		// Search backwards for first token that isn't whitespace, is it a "->" operator?
		$objectOperatorPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true);
		if (($objectOperatorPtr === false) || ($tokens[$objectOperatorPtr]['code'] !== T_OBJECT_OPERATOR)) {
			return false;
		}

		$this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
		return true;
	}

	/**
	 * Process a normal variable in the code.
	 *
	 * Most importantly, this function determines if the variable use is a "read"
	 * (using the variable for something) or a "write" (an assignment) or,
	 * sometimes, both at once.
	 *
	 * It also determines the scope of the variable (where it begins and ends).
	 *
	 * Using these two pieces of information, we can determine if the variable is
	 * being used ("read") without having been defined ("write").
	 *
	 * We can also determine, once the scan has hit the end of a scope, if any of
	 * the variables within that scope have been defined ("write") without being
	 * used ("read"). That behavior, however, happens in the `processScopeClose()`
	 * function using the data gathered by this function.
	 *
	 * Some variables are used in more complex ways, so there are other similar
	 * functions to this one, like `processVariableInString`, and
	 * `processCompact`. They have the same purpose as this function, though.
	 *
	 * If the 'ignore-for-loops' option is true, we will ignore the special
	 * processing for the increment variables of for loops. This will prevent
	 * infinite loops.
	 *
	 * @param File                           $phpcsFile The PHP_CodeSniffer file where this token was found.
	 * @param int                            $stackPtr  The position where the token was found.
	 * @param array<string, bool|string|int> $options   See above.
	 *
	 * @return void
	 */
	protected function processVariable(File $phpcsFile, $stackPtr, $options = [])
	{
		$tokens = $phpcsFile->getTokens();
		$token  = $tokens[$stackPtr];

		// Get the name of the variable.
		$varName = Helpers::normalizeVarName($token['content']);
		Helpers::debug("examining token for variable '{$varName}' on line {$token['line']}", $token);

		// Find the start of the current scope.
		$currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);
		if ($currScope === null) {
			Helpers::debug('no scope found');
			return;
		}
		Helpers::debug("start of scope for variable '{$varName}' is", $currScope);

		// Determine if variable is being assigned ("write") or used ("read").

		// Read methods that preempt assignment:
		//   Are we a $object->$property type symbolic reference?

		// Possible assignment methods:
		//   Is a mandatory function/closure parameter
		//   Is an optional function/closure parameter with non-null value
		//   Is closure use declaration of a variable defined within containing scope
		//   catch (...) block start
		//   $this within a class.
		//   $GLOBALS, $_REQUEST, etc superglobals.
		//   $var part of class::$var static member
		//   Assignment via =
		//   Assignment via list (...) =
		//   Declares as a global
		//   Declares as a static
		//   Assignment via foreach (... as ...) { }
		//   Pass-by-reference to known pass-by-reference function

		// Are we inside the third expression of a for loop? Store such variables
		// for processing after the loop ends by `processClosingForLoopsAt()`.
		if (empty($options['ignore-for-loops'])) {
			$forLoop = Helpers::getForLoopForIncrementVariable($stackPtr, $this->forLoops);
			if ($forLoop) {
				Helpers::debug('found variable inside for loop third expression');
				$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
				$forLoop->incrementVariables[$stackPtr] = $varInfo;
				return;
			}
		}

		// Are we a $object->$property type symbolic reference?
		if ($this->processVariableAsSymbolicObjectProperty($phpcsFile, $stackPtr, $varName, $currScope)) {
			Helpers::debug('found symbolic object property');
			return;
		}

		// Are we a function or closure parameter?
		if (Helpers::isTokenFunctionParameter($phpcsFile, $stackPtr)) {
			Helpers::debug('found function definition parameter');
			$this->processVariableAsFunctionParameter($phpcsFile, $stackPtr, $varName, $currScope);
			return;
		}

		// Are we a variable being imported into a function's scope with "use"?
		if (Helpers::isTokenInsideFunctionUseImport($phpcsFile, $stackPtr)) {
			Helpers::debug('found use scope import definition');
			$this->processVariableAsUseImportDefinition($phpcsFile, $stackPtr, $varName, $currScope);
			return;
		}

		// Are we a catch parameter?
		if ($this->processVariableAsCatchBlock($phpcsFile, $stackPtr, $varName, $currScope)) {
			Helpers::debug('found catch block');
			return;
		}

		// Are we $this within a class?
		if ($this->processVariableAsThisWithinClass($phpcsFile, $stackPtr, $varName)) {
			Helpers::debug('found this usage within a class');
			return;
		}

		// Are we a $GLOBALS, $_REQUEST, etc superglobal?
		if ($this->processVariableAsSuperGlobal($varName)) {
			Helpers::debug('found superglobal');
			return;
		}

		// Check for static members used outside a class
		if ($this->processVariableAsStaticOutsideClass($phpcsFile, $stackPtr, $varName)) {
			Helpers::debug('found static usage outside of class');
			return;
		}

		// $var part of class::$var static member
		if ($this->processVariableAsStaticMember($phpcsFile, $stackPtr)) {
			Helpers::debug('found static member');
			return;
		}

		if ($this->processVariableAsClassProperty($phpcsFile, $stackPtr)) {
			Helpers::debug('found class property definition');
			return;
		}

		// Is the next non-whitespace an assignment?
		if (Helpers::isTokenInsideAssignmentLHS($phpcsFile, $stackPtr)) {
			Helpers::debug('found assignment');
			$this->processVariableAsAssignment($phpcsFile, $stackPtr, $varName, $currScope);
			if (Helpers::isTokenInsideAssignmentRHS($phpcsFile, $stackPtr) || Helpers::isTokenInsideFunctionCallArgument($phpcsFile, $stackPtr)) {
				Helpers::debug("found assignment that's also inside an expression");
				$this->markVariableRead($varName, $stackPtr, $currScope);
				return;
			}
			return;
		}

		// OK, are we within a list (...) = construct?
		if ($this->processVariableAsListAssignment($phpcsFile, $stackPtr, $varName, $currScope)) {
			Helpers::debug('found list assignment');
			return;
		}

		// OK, are we within a [...] = construct?
		if ($this->processVariableAsListShorthandAssignment($phpcsFile, $stackPtr, $varName, $currScope)) {
			Helpers::debug('found list shorthand assignment');
			return;
		}

		// Are we a global declaration?
		if ($this->processVariableAsGlobalDeclaration($phpcsFile, $stackPtr, $varName, $currScope)) {
			Helpers::debug('found global declaration');
			return;
		}

		// Are we a static declaration?
		if ($this->processVariableAsStaticDeclaration($phpcsFile, $stackPtr, $varName, $currScope)) {
			Helpers::debug('found static declaration');
			return;
		}

		// Are we a foreach loopvar?
		if ($this->processVariableAsForeachLoopVar($phpcsFile, $stackPtr, $varName, $currScope)) {
			Helpers::debug('found foreach loop variable');
			return;
		}

		// Are we pass-by-reference to known pass-by-reference function?
		if ($this->processVariableAsPassByReferenceFunctionCall($phpcsFile, $stackPtr, $varName, $currScope)) {
			Helpers::debug('found pass by reference');
			return;
		}

		// Are we a numeric variable used for constructs like preg_replace?
		if (Helpers::isVariableANumericVariable($varName)) {
			Helpers::debug('found numeric variable');
			return;
		}

		if (Helpers::isVariableInsideElseCondition($phpcsFile, $stackPtr) || Helpers::isVariableInsideElseBody($phpcsFile, $stackPtr)) {
			Helpers::debug('found variable inside else condition or body');
			$this->processVaribleInsideElse($phpcsFile, $stackPtr, $varName, $currScope);
			return;
		}

		// Are we an isset or empty call?
		if (Helpers::isVariableInsideIssetOrEmpty($phpcsFile, $stackPtr)) {
			Helpers::debug('found isset or empty');
			$this->markVariableRead($varName, $stackPtr, $currScope);
			return;
		}

		// OK, we don't appear to be a write to the var, assume we're a read.
		Helpers::debug('looks like a variable read');
		$this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
	}

	/**
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName
	 * @param int    $currScope
	 *
	 * @return void
	 */
	protected function processVaribleInsideElse(File $phpcsFile, $stackPtr, $varName, $currScope)
	{
		// Find all assignments to this variable inside the current scope.
		$varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
		$allAssignmentIndices = array_unique($varInfo->allAssignments);
		// Find the attached 'if' and 'elseif' block start and end indices.
		$blockIndices = Helpers::getAttachedBlockIndicesForElse($phpcsFile, $stackPtr);

		// If all of the assignments are within the previous attached blocks, then warn about undefined.
		$tokens = $phpcsFile->getTokens();
		$assignmentsInsideAttachedBlocks = [];
		foreach ($allAssignmentIndices as $index) {
			foreach ($blockIndices as $blockIndex) {
				$blockToken = $tokens[$blockIndex];
				Helpers::debug('for variable inside else, looking at assignment', $index, 'at block index', $blockIndex, 'which is token', $blockToken);
				if (isset($blockToken['scope_opener']) && isset($blockToken['scope_closer'])) {
					$scopeOpener = $blockToken['scope_opener'];
					$scopeCloser = $blockToken['scope_closer'];
				} else {
					// If the `if` statement has no scope, it is probably inline, which
					// means its scope is from the end of the condition up until the next
					// semicolon
					$scopeOpener = isset($blockToken['parenthesis_closer']) ? $blockToken['parenthesis_closer'] : $blockIndex + 1;
					$scopeCloser = $phpcsFile->findNext([T_SEMICOLON], $scopeOpener);
					if (! $scopeCloser) {
						throw new \Exception("Cannot find scope for if condition block at index {$stackPtr} while examining variable {$varName}");
					}
				}
				Helpers::debug('for variable inside else, looking at scope', $index, 'between', $scopeOpener, 'and', $scopeCloser);
				if (Helpers::isIndexInsideScope($index, $scopeOpener, $scopeCloser)) {
					$assignmentsInsideAttachedBlocks[] = $index;
				}
			}
		}

		if (count($assignmentsInsideAttachedBlocks) === count($allAssignmentIndices)) {
			if (! $varInfo->ignoreUndefined) {
				Helpers::debug("variable $varName inside else looks undefined");
				$this->warnAboutUndefinedVariable($phpcsFile, $varName, $stackPtr);
			}
			return;
		}

		Helpers::debug('looks like a variable read inside else');
		$this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
	}

	/**
	 * Called to process variables found in double quoted strings.
	 *
	 * Note that there may be more than one variable in the string, which will
	 * result only in one call for the string.
	 *
	 * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
	 * @param int  $stackPtr  The position where the double quoted string was found.
	 *
	 * @return void
	 */
	protected function processVariableInString(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token  = $tokens[$stackPtr];

		if (!preg_match_all(Constants::getDoubleQuotedVarRegexp(), $token['content'], $matches)) {
			return;
		}
		Helpers::debug('examining token for variable in string', $token);

		foreach ($matches[1] as $varName) {
			$varName = Helpers::normalizeVarName($varName);

			// Are we $this within a class?
			if ($this->processVariableAsThisWithinClass($phpcsFile, $stackPtr, $varName)) {
				continue;
			}

			if ($this->processVariableAsSuperGlobal($varName)) {
				continue;
			}

			// Are we a numeric variable used for constructs like preg_replace?
			if (Helpers::isVariableANumericVariable($varName)) {
				continue;
			}

			$currScope = Helpers::findVariableScope($phpcsFile, $stackPtr, $varName);
			if ($currScope === null) {
				continue;
			}

			$this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
		}
	}

	/**
	 * @param File                   $phpcsFile
	 * @param int                    $stackPtr
	 * @param array<int, array<int>> $arguments The stack pointers of each argument
	 * @param int                    $currScope
	 *
	 * @return void
	 */
	protected function processCompactArguments(File $phpcsFile, $stackPtr, $arguments, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		foreach ($arguments as $argumentPtrs) {
			$argumentPtrs = array_values(array_filter($argumentPtrs, function ($argumentPtr) use ($tokens) {
				return isset(Tokens::$emptyTokens[$tokens[$argumentPtr]['code']]) === false;
			}));
			if (empty($argumentPtrs)) {
				continue;
			}
			if (!isset($tokens[$argumentPtrs[0]])) {
				continue;
			}
			$argumentFirstToken = $tokens[$argumentPtrs[0]];
			if ($argumentFirstToken['code'] === T_ARRAY) {
				// It's an array argument, recurse.
				$arrayArguments = Helpers::findFunctionCallArguments($phpcsFile, $argumentPtrs[0]);
				$this->processCompactArguments($phpcsFile, $stackPtr, $arrayArguments, $currScope);
				continue;
			}
			if (count($argumentPtrs) > 1) {
				// Complex argument, we can't handle it, ignore.
				continue;
			}
			if ($argumentFirstToken['code'] === T_CONSTANT_ENCAPSED_STRING) {
				// Single-quoted string literal, ie compact('whatever').
				// Substr is to strip the enclosing single-quotes.
				$varName = substr($argumentFirstToken['content'], 1, -1);
				$this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $argumentPtrs[0], $currScope);
				continue;
			}
			if ($argumentFirstToken['code'] === T_DOUBLE_QUOTED_STRING) {
				// Double-quoted string literal.
				if (preg_match(Constants::getDoubleQuotedVarRegexp(), $argumentFirstToken['content'])) {
					// Bail if the string needs variable expansion, that's runtime stuff.
					continue;
				}
				// Substr is to strip the enclosing double-quotes.
				$varName = substr($argumentFirstToken['content'], 1, -1);
				$this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $argumentPtrs[0], $currScope);
				continue;
			}
		}
	}

	/**
	 * Called to process variables named in a call to compact().
	 *
	 * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
	 * @param int  $stackPtr  The position where the call to compact() was found.
	 *
	 * @return void
	 */
	protected function processCompact(File $phpcsFile, $stackPtr)
	{
		$currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);
		if ($currScope === null) {
			return;
		}

		$arguments = Helpers::findFunctionCallArguments($phpcsFile, $stackPtr);
		$this->processCompactArguments($phpcsFile, $stackPtr, $arguments, $currScope);
	}

	/**
	 * Called to process the end of a scope.
	 *
	 * Note that although triggered by the closing curly brace of the scope,
	 * $stackPtr is the scope conditional, not the closing curly brace.
	 *
	 * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
	 * @param int  $stackPtr  The position of the scope conditional.
	 *
	 * @return void
	 */
	protected function processScopeClose(File $phpcsFile, $stackPtr)
	{
		$scopeInfo = $this->scopeManager->getScopeForScopeStart($phpcsFile->getFilename(), $stackPtr);
		if (is_null($scopeInfo)) {
			return;
		}
		foreach ($scopeInfo->variables as $varInfo) {
			$this->processScopeCloseForVariable($phpcsFile, $varInfo, $scopeInfo);
		}
	}

	/**
	 * Warn about an unused variable if it has not been used within a scope.
	 *
	 * @param File         $phpcsFile
	 * @param VariableInfo $varInfo
	 * @param ScopeInfo    $scopeInfo
	 *
	 * @return void
	 */
	protected function processScopeCloseForVariable(File $phpcsFile, VariableInfo $varInfo, ScopeInfo $scopeInfo)
	{
		Helpers::debug('processScopeCloseForVariable', $varInfo);
		if ($varInfo->ignoreUnused || isset($varInfo->firstRead)) {
			return;
		}
		if ($this->allowUnusedFunctionParameters && $varInfo->scopeType === ScopeType::PARAM) {
			return;
		}
		if ($this->allowUnusedParametersBeforeUsed && $varInfo->scopeType === ScopeType::PARAM && Helpers::areFollowingArgumentsUsed($varInfo, $scopeInfo)) {
			Helpers::debug("variable {$varInfo->name} at end of scope has unused following args");
			return;
		}
		if ($this->allowUnusedForeachVariables && $varInfo->isForeachLoopAssociativeValue) {
			return;
		}
		if ($varInfo->referencedVariableScope !== null && isset($varInfo->firstInitialized)) {
			// If we're pass-by-reference then it's a common pattern to
			// use the variable to return data to the caller, so any
			// assignment also counts as "variable use" for the purposes
			// of "unused variable" warnings.
			return;
		}
		if ($varInfo->scopeType === ScopeType::GLOBALSCOPE && isset($varInfo->firstInitialized)) {
			// If we imported this variable from the global scope, any further use of
			// the variable, including assignment, should count as "variable use" for
			// the purposes of "unused variable" warnings.
			return;
		}
		if (empty($varInfo->firstDeclared) && empty($varInfo->firstInitialized)) {
			return;
		}
		if ($this->allowUnusedVariablesBeforeRequire && Helpers::isRequireInScopeAfter($phpcsFile, $varInfo, $scopeInfo)) {
			return;
		}
		if ($scopeInfo->scopeStartIndex === 0 && $this->allowUnusedVariablesInFileScope) {
			return;
		}
		if (
			! empty($varInfo->firstDeclared)
			&& $varInfo->scopeType === ScopeType::PARAM
			&& Helpers::isInAbstractClass(
				$phpcsFile,
				Helpers::getFunctionIndexForFunctionParameter($phpcsFile, $varInfo->firstDeclared) ?: 0
			)
			&& Helpers::isFunctionBodyEmpty(
				$phpcsFile,
				Helpers::getFunctionIndexForFunctionParameter($phpcsFile, $varInfo->firstDeclared) ?: 0
			)
		) {
			// Allow non-abstract methods inside an abstract class to have unused
			// parameters if the method body does nothing. Such methods are
			// effectively optional abstract methods so their unused parameters
			// should be ignored as we do with abstract method parameters.
			return;
		}

		$this->warnAboutUnusedVariable($phpcsFile, $varInfo);
	}

	/**
	 * Register warnings for a variable that is defined but not used.
	 *
	 * @param File         $phpcsFile
	 * @param VariableInfo $varInfo
	 *
	 * @return void
	 */
	protected function warnAboutUnusedVariable(File $phpcsFile, VariableInfo $varInfo)
	{
		foreach (array_unique($varInfo->allAssignments) as $indexForWarning) {
			Helpers::debug("variable {$varInfo->name} at end of scope looks unused");
			$phpcsFile->addWarning(
				'Unused %s %s.',
				$indexForWarning,
				'UnusedVariable',
				[
					VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType ?: ScopeType::LOCAL],
					"\${$varInfo->name}",
				]
			);
		}
	}

	/**
	 * @param File   $phpcsFile
	 * @param string $varName
	 * @param int    $stackPtr
	 *
	 * @return void
	 */
	protected function warnAboutUndefinedVariable(File $phpcsFile, $varName, $stackPtr)
	{
		$phpcsFile->addWarning(
			'Variable %s is undefined.',
			$stackPtr,
			'UndefinedVariable',
			["\${$varName}"]
		);
	}

	/**
	 * @param File   $phpcsFile
	 * @param string $varName
	 * @param int    $stackPtr
	 *
	 * @return void
	 */
	protected function warnAboutUndefinedArrayPushShortcut(File $phpcsFile, $varName, $stackPtr)
	{
		$phpcsFile->addWarning(
			'Array variable %s is undefined.',
			$stackPtr,
			'UndefinedVariable',
			["\${$varName}"]
		);
	}

	/**
	 * @param File   $phpcsFile
	 * @param string $varName
	 * @param int    $stackPtr
	 *
	 * @return void
	 */
	protected function warnAboutUndefinedUnset(File $phpcsFile, $varName, $stackPtr)
	{
		$phpcsFile->addWarning(
			'Variable %s inside unset call is undefined.',
			$stackPtr,
			'UndefinedUnsetVariable',
			["\${$varName}"]
		);
	}
}
