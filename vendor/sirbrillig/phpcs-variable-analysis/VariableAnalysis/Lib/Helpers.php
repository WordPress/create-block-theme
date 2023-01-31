<?php

namespace VariableAnalysis\Lib;

use PHP_CodeSniffer\Files\File;
use VariableAnalysis\Lib\ScopeInfo;
use VariableAnalysis\Lib\ForLoopInfo;
use VariableAnalysis\Lib\ScopeType;
use VariableAnalysis\Lib\VariableInfo;
use PHP_CodeSniffer\Util\Tokens;

class Helpers
{
	/**
	 * @return array<int|string>
	 */
	public static function getPossibleEndOfFileTokens()
	{
		return array_merge(
			array_values(Tokens::$emptyTokens),
			[
				T_INLINE_HTML,
				T_CLOSE_TAG,
			]
		);
	}

	/**
	 * @param int|bool $value
	 *
	 * @return ?int
	 */
	public static function getIntOrNull($value)
	{
		return is_int($value) ? $value : null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function findContainingOpeningSquareBracket(File $phpcsFile, $stackPtr)
	{
		$previousStatementPtr = self::getPreviousStatementPtr($phpcsFile, $stackPtr);
		return self::getIntOrNull($phpcsFile->findPrevious([T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET], $stackPtr - 1, $previousStatementPtr));
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return int
	 */
	public static function getPreviousStatementPtr(File $phpcsFile, $stackPtr)
	{
		$result = $phpcsFile->findPrevious([T_SEMICOLON, T_CLOSE_CURLY_BRACKET], $stackPtr - 1);
		return is_bool($result) ? 1 : $result;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function findContainingOpeningBracket(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
			/**
			 * @var array<int|string|null>
			 */
			$openPtrs = array_keys($tokens[$stackPtr]['nested_parenthesis']);
			return (int)end($openPtrs);
		}
		return null;
	}

	/**
	 * @param (int|string)[] $conditions
	 *
	 * @return bool
	 */
	public static function areAnyConditionsAClass(array $conditions)
	{
		foreach (array_reverse($conditions, true) as $scopeCode) {
			if ($scopeCode === T_CLASS || $scopeCode === T_ANON_CLASS || $scopeCode === T_TRAIT) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return true if the token conditions are within a function before they are
	 * within a class.
	 *
	 * @param (int|string)[] $conditions
	 *
	 * @return bool
	 */
	public static function areConditionsWithinFunctionBeforeClass(array $conditions)
	{
		$classTypes = [T_CLASS, T_ANON_CLASS, T_TRAIT];
		foreach (array_reverse($conditions, true) as $scopeCode) {
			if (in_array($scopeCode, $classTypes)) {
				return false;
			}
			if ($scopeCode === T_FUNCTION) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return true if the token conditions are within an if block before they are
	 * within a class or function.
	 *
	 * @param (int|string)[] $conditions
	 *
	 * @return int|string|null
	 */
	public static function getClosestIfPositionIfBeforeOtherConditions(array $conditions)
	{
		$conditionsInsideOut = array_reverse($conditions, true);
		if (empty($conditions)) {
			return null;
		}
		$scopeCode = reset($conditionsInsideOut);
		if ($scopeCode === T_IF) {
			return key($conditionsInsideOut);
		}
		return null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenFunctionParameter(File $phpcsFile, $stackPtr)
	{
		return is_int(self::getFunctionIndexForFunctionParameter($phpcsFile, $stackPtr));
	}

	/**
	 * Return true if the token is inside the arguments of a function call.
	 *
	 * For example, the variable `$foo` in `doSomething($foo)` is inside the
	 * arguments to the call to `doSomething()`.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideFunctionCallArgument(File $phpcsFile, $stackPtr)
	{
		return is_int(self::getFunctionIndexForFunctionCallArgument($phpcsFile, $stackPtr));
	}

	/**
	 * Find the index of the function keyword for a token in a function
	 * definition's parameters.
	 *
	 * Does not work for tokens inside the "use".
	 *
	 * Will also work for the parenthesis that make up the function definition's
	 * parameters list.
	 *
	 * For arguments inside a function call, rather than a definition, use
	 * `getFunctionIndexForFunctionCallArgument`.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getFunctionIndexForFunctionParameter(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		if ($token['code'] === 'PHPCS_T_OPEN_PARENTHESIS') {
			$startOfArguments = $stackPtr;
		} elseif ($token['code'] === 'PHPCS_T_CLOSE_PARENTHESIS') {
			if (empty($token['parenthesis_opener'])) {
				return null;
			}
			$startOfArguments = $token['parenthesis_opener'];
		} else {
			if (empty($token['nested_parenthesis'])) {
				return null;
			}
			$startingParenthesis = array_keys($token['nested_parenthesis']);
			$startOfArguments = end($startingParenthesis);
		}

		if (! is_int($startOfArguments)) {
			return null;
		}

		$nonFunctionTokenTypes = Tokens::$emptyTokens;
		$nonFunctionTokenTypes[] = T_STRING;
		$nonFunctionTokenTypes[] = T_BITWISE_AND;
		$functionPtr = self::getIntOrNull($phpcsFile->findPrevious($nonFunctionTokenTypes, $startOfArguments - 1, null, true, null, true));
		if (! is_int($functionPtr)) {
			return null;
		}
		$functionToken = $tokens[$functionPtr];

		$functionTokenTypes = [
			T_FUNCTION,
			T_CLOSURE,
		];
		if (!in_array($functionToken['code'], $functionTokenTypes, true) && ! self::isArrowFunction($phpcsFile, $functionPtr)) {
			return null;
		}
		return $functionPtr;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideFunctionUseImport(File $phpcsFile, $stackPtr)
	{
		return is_int(self::getUseIndexForUseImport($phpcsFile, $stackPtr));
	}

	/**
	 * Find the token index of the "use" for a token inside a function use import
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getUseIndexForUseImport(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$nonUseTokenTypes = Tokens::$emptyTokens;
		$nonUseTokenTypes[] = T_VARIABLE;
		$nonUseTokenTypes[] = T_ELLIPSIS;
		$nonUseTokenTypes[] = T_COMMA;
		$nonUseTokenTypes[] = T_BITWISE_AND;
		$openParenPtr = self::getIntOrNull($phpcsFile->findPrevious($nonUseTokenTypes, $stackPtr - 1, null, true, null, true));
		if (! is_int($openParenPtr) || $tokens[$openParenPtr]['code'] !== T_OPEN_PARENTHESIS) {
			return null;
		}

		$usePtr = self::getIntOrNull($phpcsFile->findPrevious(array_values($nonUseTokenTypes), $openParenPtr - 1, null, true, null, true));
		if (! is_int($usePtr) || $tokens[$usePtr]['code'] !== T_USE) {
			return null;
		}
		return $usePtr;
	}

	/**
	 * Return the index of a function's name token from inside the function.
	 *
	 * $stackPtr must be inside the function body or parameters for this to work.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function findFunctionCall(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$openPtr = self::findContainingOpeningBracket($phpcsFile, $stackPtr);
		if (is_int($openPtr)) {
			// First non-whitespace thing and see if it's a T_STRING function name
			$functionPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $openPtr - 1, null, true, null, true);
			if (is_int($functionPtr) && $tokens[$functionPtr]['code'] === T_STRING) {
				return $functionPtr;
			}
		}
		return null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return array<int, array<int>>
	 */
	public static function findFunctionCallArguments(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		// Slight hack: also allow this to find args for array constructor.
		if (($tokens[$stackPtr]['code'] !== T_STRING) && ($tokens[$stackPtr]['code'] !== T_ARRAY)) {
			// Assume $stackPtr is something within the brackets, find our function call
			$stackPtr = self::findFunctionCall($phpcsFile, $stackPtr);
			if ($stackPtr === null) {
				return [];
			}
		}

		// $stackPtr is the function name, find our brackets after it
		$openPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true);
		if (($openPtr === false) || ($tokens[$openPtr]['code'] !== T_OPEN_PARENTHESIS)) {
			return [];
		}

		if (!isset($tokens[$openPtr]['parenthesis_closer'])) {
			return [];
		}
		$closePtr = $tokens[$openPtr]['parenthesis_closer'];

		$argPtrs = [];
		$lastPtr = $openPtr;
		$lastArgComma = $openPtr;
		$nextPtr = $phpcsFile->findNext([T_COMMA], $lastPtr + 1, $closePtr);
		while (is_int($nextPtr)) {
			if (self::findContainingOpeningBracket($phpcsFile, $nextPtr) === $openPtr) {
				// Comma is at our level of brackets, it's an argument delimiter.
				$range = range($lastArgComma + 1, $nextPtr - 1);
				$range = array_filter($range, function ($element) {
					return is_int($element);
				});
				array_push($argPtrs, $range);
				$lastArgComma = $nextPtr;
			}
			$lastPtr = $nextPtr;
			$nextPtr = $phpcsFile->findNext([T_COMMA], $lastPtr + 1, $closePtr);
		}
		$range = range($lastArgComma + 1, $closePtr - 1);
		$range = array_filter($range, function ($element) {
			return is_int($element);
		});
		array_push($argPtrs, $range);

		return $argPtrs;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getNextAssignPointer(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		// Is the next non-whitespace an assignment?
		$nextPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true);
		if (is_int($nextPtr)
			&& isset(Tokens::$assignmentTokens[$tokens[$nextPtr]['code']])
			// Ignore double arrow to prevent triggering on `foreach ( $array as $k => $v )`.
			&& $tokens[$nextPtr]['code'] !== T_DOUBLE_ARROW
		) {
			return $nextPtr;
		}
		return null;
	}

	/**
	 * @param string $varName
	 *
	 * @return string
	 */
	public static function normalizeVarName($varName)
	{
		$result = preg_replace('/[{}$]/', '', $varName);
		return $result ? $result : $varName;
	}

	/**
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName   (optional) if it differs from the normalized 'content' of the token at $stackPtr
	 *
	 * @return ?int
	 */
	public static function findVariableScope(File $phpcsFile, $stackPtr, $varName = null)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		$varName = isset($varName) ? $varName : self::normalizeVarName($token['content']);

		$arrowFunctionIndex = self::getContainingArrowFunctionIndex($phpcsFile, $stackPtr);
		$isTokenInsideArrowFunctionBody = is_int($arrowFunctionIndex);
		if ($isTokenInsideArrowFunctionBody) {
			// Get the list of variables defined by the arrow function
			// If this matches any of them, the scope is the arrow function,
			// otherwise, it uses the enclosing scope.
			if ($arrowFunctionIndex) {
				$variableNames = self::getVariablesDefinedByArrowFunction($phpcsFile, $arrowFunctionIndex);
				self::debug('findVariableScope: looking for', $varName, 'in arrow function variables', $variableNames);
				if (in_array($varName, $variableNames, true)) {
					return $arrowFunctionIndex;
				}
			}
		}

		return self::findVariableScopeExceptArrowFunctions($phpcsFile, $stackPtr);
	}

	/**
	 * Return the token index of the scope start for a token
	 *
	 * For a variable within a function body, or a variable within a function
	 * definition argument list, this will return the function keyword's index.
	 *
	 * For a variable within a "use" import list within a function definition,
	 * this will return the enclosing scope, not the function keyword. This is
	 * important to note because the "use" keyword performs double-duty, defining
	 * variables for the function's scope, and consuming the variables in the
	 * enclosing scope. Use `getUseIndexForUseImport` to determine if this
	 * token needs to be treated as a "use".
	 *
	 * For a variable within an arrow function definition argument list,
	 * this will return the arrow function's keyword index.
	 *
	 * For a variable in an arrow function body, this will return the enclosing
	 * function's index, which may be incorrect.
	 *
	 * Since a variable in an arrow function's body may be imported from the
	 * enclosing scope, it's important to test to see if the variable is in an
	 * arrow function and also check its enclosing scope separately.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function findVariableScopeExceptArrowFunctions(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$allowedTypes = [
			T_VARIABLE,
			T_DOUBLE_QUOTED_STRING,
			T_HEREDOC,
			T_STRING,
		];
		if (! in_array($tokens[$stackPtr]['code'], $allowedTypes, true)) {
			throw new \Exception("Cannot find variable scope for non-variable {$tokens[$stackPtr]['type']}");
		}

		$startOfTokenScope = self::getStartOfTokenScope($phpcsFile, $stackPtr);
		if (is_int($startOfTokenScope) && $startOfTokenScope > 0) {
			return $startOfTokenScope;
		}

		// If there is no "conditions" array, this is a function definition argument.
		if (self::isTokenFunctionParameter($phpcsFile, $stackPtr)) {
			$functionPtr = self::getFunctionIndexForFunctionParameter($phpcsFile, $stackPtr);
			if (! is_int($functionPtr)) {
				throw new \Exception("Function index not found for function argument index {$stackPtr}");
			}
			return $functionPtr;
		}

		self::debug('Cannot find function scope for variable at', $stackPtr);
		return $startOfTokenScope;
	}

	/**
	 * Return the token index of the scope start for a variable token
	 *
	 * This will only work for a variable within a function's body. Otherwise,
	 * see `findVariableScope`, which is more complex.
	 *
	 * Note that if used on a variable in an arrow function, it will return the
	 * enclosing function's scope, which may be incorrect.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	private static function getStartOfTokenScope(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];

		$inClass = false;
		$conditions = isset($token['conditions']) ? $token['conditions'] : [];
		$functionTokenTypes = [
			T_FUNCTION,
			T_CLOSURE,
		];
		foreach (array_reverse($conditions, true) as $scopePtr => $scopeCode) {
			if (in_array($scopeCode, $functionTokenTypes, true) || self::isArrowFunction($phpcsFile, $scopePtr)) {
				return $scopePtr;
			}
			if (isset(Tokens::$ooScopeTokens[$scopeCode]) === true) {
				$inClass = true;
			}
		}

		if ($inClass) {
			// If this is inside a class and not inside a function, this is either a
			// class member variable definition, or a function argument. If it is a
			// variable definition, it has no scope on its own (it can only be used
			// with an object reference). If it is a function argument, we need to do
			// more work (see `findVariableScopeExceptArrowFunctions`).
			return null;
		}

		// If we can't find a scope, let's use the first token of the file.
		return 0;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideArrowFunctionDefinition(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		$openParenIndices = isset($token['nested_parenthesis']) ? $token['nested_parenthesis'] : [];
		if (empty($openParenIndices)) {
			return false;
		}
		$openParenPtr = $openParenIndices[0];
		return self::isArrowFunction($phpcsFile, $openParenPtr - 1);
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getContainingArrowFunctionIndex(File $phpcsFile, $stackPtr)
	{
		$arrowFunctionIndex = self::getPreviousArrowFunctionIndex($phpcsFile, $stackPtr);
		if (! is_int($arrowFunctionIndex)) {
			return null;
		}
		$arrowFunctionInfo = self::getArrowFunctionOpenClose($phpcsFile, $arrowFunctionIndex);
		if (! $arrowFunctionInfo) {
			return null;
		}
		$arrowFunctionScopeStart = $arrowFunctionInfo['scope_opener'];
		$arrowFunctionScopeEnd = $arrowFunctionInfo['scope_closer'];
		if ($stackPtr > $arrowFunctionScopeStart && $stackPtr < $arrowFunctionScopeEnd) {
			return $arrowFunctionIndex;
		}
		return null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	private static function getPreviousArrowFunctionIndex(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$enclosingScopeIndex = self::findVariableScopeExceptArrowFunctions($phpcsFile, $stackPtr);
		for ($index = $stackPtr - 1; $index > $enclosingScopeIndex; $index--) {
			$token = $tokens[$index];
			if ($token['content'] === 'fn' && self::isArrowFunction($phpcsFile, $index)) {
				return $index;
			}
		}
		return null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isArrowFunction(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		if (defined('T_FN') && $tokens[$stackPtr]['code'] === T_FN) {
			return true;
		}
		if ($tokens[$stackPtr]['content'] !== 'fn') {
			return false;
		}
		// Make sure next non-space token is an open parenthesis
		$openParenIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		if (! is_int($openParenIndex) || $tokens[$openParenIndex]['code'] !== T_OPEN_PARENTHESIS) {
			return false;
		}
		// Find the associated close parenthesis
		$closeParenIndex = $tokens[$openParenIndex]['parenthesis_closer'];
		// Make sure the next token is a fat arrow
		$fatArrowIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $closeParenIndex + 1, null, true);
		if (! is_int($fatArrowIndex)) {
			return false;
		}
		if ($tokens[$fatArrowIndex]['code'] !== T_DOUBLE_ARROW && $tokens[$fatArrowIndex]['type'] !== 'T_FN_ARROW') {
			return false;
		}
		return true;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?array<string, int>
	 */
	public static function getArrowFunctionOpenClose(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		if (defined('T_FN') && $tokens[$stackPtr]['code'] === T_FN) {
			return [
				'scope_opener' => $tokens[$stackPtr]['scope_opener'],
				'scope_closer' => $tokens[$stackPtr]['scope_closer'],
			];
		}
		if ($tokens[$stackPtr]['content'] !== 'fn') {
			return null;
		}
		// Make sure next non-space token is an open parenthesis
		$openParenIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		if (! is_int($openParenIndex) || $tokens[$openParenIndex]['code'] !== T_OPEN_PARENTHESIS) {
			return null;
		}
		// Find the associated close parenthesis
		$closeParenIndex = $tokens[$openParenIndex]['parenthesis_closer'];
		// Make sure the next token is a fat arrow
		$fatArrowIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $closeParenIndex + 1, null, true);
		if (! is_int($fatArrowIndex)) {
			return null;
		}
		if ($tokens[$fatArrowIndex]['code'] !== T_DOUBLE_ARROW && $tokens[$fatArrowIndex]['type'] !== 'T_FN_ARROW') {
			return null;
		}
		// Find the scope closer
		$endScopeTokens = [
			T_COMMA,
			T_SEMICOLON,
			T_CLOSE_PARENTHESIS,
			T_CLOSE_CURLY_BRACKET,
			T_CLOSE_SHORT_ARRAY,
		];
		$scopeCloserIndex = $phpcsFile->findNext($endScopeTokens, $fatArrowIndex	+ 1);
		if (! is_int($scopeCloserIndex)) {
			return null;
		}
		return [
			'scope_opener' => $stackPtr,
			'scope_closer' => $scopeCloserIndex,
		];
	}

	/**
	 * Determine if a token is a list opener for list assignment/destructuring.
	 *
	 * The index provided can be either the opening square brace of a short list
	 * assignment like the first character of `[$a] = $b;` or the `list` token of
	 * an expression like `list($a) = $b;` or the opening parenthesis of that
	 * expression.
	 *
	 * @param File $phpcsFile
	 * @param int  $listOpenerIndex
	 *
	 * @return bool
	 */
	private static function isListAssignment(File $phpcsFile, $listOpenerIndex)
	{
		$tokens = $phpcsFile->getTokens();
		// Match `[$a] = $b;` except for when the previous token is a parenthesis.
		if ($tokens[$listOpenerIndex]['code'] === T_OPEN_SHORT_ARRAY) {
			return true;
		}
		// Match `list($a) = $b;`
		if ($tokens[$listOpenerIndex]['code'] === T_LIST) {
			return true;
		}

		// If $listOpenerIndex is the open parenthesis of `list($a) = $b;`, then
		// match that too.
		if ($tokens[$listOpenerIndex]['code'] === T_OPEN_PARENTHESIS) {
			$previousTokenPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $listOpenerIndex - 1, null, true);
			if (
				isset($tokens[$previousTokenPtr])
				&& $tokens[$previousTokenPtr]['code'] === T_LIST
			) {
				return true;
			}
			return true;
		}

		// If the list opener token is a square bracket that is preceeded by a
		// close parenthesis that has an owner which is a scope opener, then this
		// is a list assignment and not an array access.
		//
		// Match `if (true) [$a] = $b;`
		if ($tokens[$listOpenerIndex]['code'] === T_OPEN_SQUARE_BRACKET) {
			$previousTokenPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $listOpenerIndex - 1, null, true);
			if (
				isset($tokens[$previousTokenPtr])
				&& $tokens[$previousTokenPtr]['code'] === T_CLOSE_PARENTHESIS
				&& isset($tokens[$previousTokenPtr]['parenthesis_owner'])
				&& isset(Tokens::$scopeOpeners[$tokens[$tokens[$previousTokenPtr]['parenthesis_owner']]['code']])
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return a list of indices for variables assigned within a list assignment.
	 *
	 * The index provided can be either the opening square brace of a short list
	 * assignment like the first character of `[$a] = $b;` or the `list` token of
	 * an expression like `list($a) = $b;` or the opening parenthesis of that
	 * expression.
	 *
	 * @param File $phpcsFile
	 * @param int  $listOpenerIndex
	 *
	 * @return ?array<int>
	 */
	public static function getListAssignments(File $phpcsFile, $listOpenerIndex)
	{
		$tokens = $phpcsFile->getTokens();
		self::debug('getListAssignments', $listOpenerIndex, $tokens[$listOpenerIndex]);

		// First find the end of the list
		$closePtr = null;
		if (isset($tokens[$listOpenerIndex]['parenthesis_closer'])) {
			$closePtr = $tokens[$listOpenerIndex]['parenthesis_closer'];
		}
		if (isset($tokens[$listOpenerIndex]['bracket_closer'])) {
			$closePtr = $tokens[$listOpenerIndex]['bracket_closer'];
		}
		if (! $closePtr) {
			return null;
		}

		// Find the assignment (equals sign) which, if this is a list assignment, should be the next non-space token
		$assignPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $closePtr + 1, null, true);

		// If the next token isn't an assignment, check for nested brackets because we might be a nested assignment
		if (! is_int($assignPtr) || $tokens[$assignPtr]['code'] !== T_EQUAL) {
			// Collect the enclosing list open/close tokens ($parents is an assoc array keyed by opener index and the value is the closer index)
			$parents = isset($tokens[$listOpenerIndex]['nested_parenthesis']) ? $tokens[$listOpenerIndex]['nested_parenthesis'] : [];
			// There's no record of nested brackets for short lists; we'll have to find the parent ourselves
			if (empty($parents)) {
				$parentSquareBracket = self::findContainingOpeningSquareBracket($phpcsFile, $listOpenerIndex);
				if (is_int($parentSquareBracket)) {
					// Collect the opening index, but we don't actually need the closing paren index so just make that 0
					$parents = [$parentSquareBracket => 0];
				}
			}
			// If we have no parents, this is not a nested assignment and therefore is not an assignment
			if (empty($parents)) {
				return null;
			}

			// Recursively check to see if the parent is a list assignment (we only need to check one level due to the recursion)
			$isNestedAssignment = null;
			$parentListOpener = array_keys(array_reverse($parents, true))[0];
			$isNestedAssignment = self::getListAssignments($phpcsFile, $parentListOpener);
			if ($isNestedAssignment === null) {
				return null;
			}
		}

		$variablePtrs = [];

		$currentPtr = $listOpenerIndex;
		$variablePtr = 0;
		while ($currentPtr < $closePtr && is_int($variablePtr)) {
			$variablePtr = $phpcsFile->findNext([T_VARIABLE], $currentPtr + 1, $closePtr);
			if (is_int($variablePtr)) {
				$variablePtrs[] = $variablePtr;
			}
			++$currentPtr;
		}

		if (! self::isListAssignment($phpcsFile, $listOpenerIndex)) {
			return null;
		}

		return $variablePtrs;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return string[]
	 */
	public static function getVariablesDefinedByArrowFunction(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$arrowFunctionToken = $tokens[$stackPtr];
		$variableNames = [];
		self::debug('looking for variables in arrow function token', $arrowFunctionToken);
		for ($index = $arrowFunctionToken['parenthesis_opener']; $index < $arrowFunctionToken['parenthesis_closer']; $index++) {
			$token = $tokens[$index];
			if ($token['code'] === T_VARIABLE) {
				$variableNames[] = self::normalizeVarName($token['content']);
			}
		}
		self::debug('found these variables in arrow function token', $variableNames);
		return $variableNames;
	}

	/**
	 * @return void
	 */
	public static function debug()
	{
		$messages = func_get_args();
		if (! defined('PHP_CODESNIFFER_VERBOSITY')) {
			return;
		}
		if (PHP_CODESNIFFER_VERBOSITY <= 3) {
			return;
		}
		$output = PHP_EOL . 'VariableAnalysisSniff: DEBUG:';
		foreach ($messages as $message) {
			if (is_string($message) || is_numeric($message)) {
				$output .= ' "' . $message . '"';
				continue;
			}
			$output .= PHP_EOL . var_export($message, true) . PHP_EOL;
		}
		$output .= PHP_EOL;
		echo $output;
	}

	/**
	 * @param string $pattern
	 * @param string $value
	 *
	 * @return string[]
	 */
	public static function splitStringToArray($pattern, $value)
	{
		$result = preg_split($pattern, $value);
		return is_array($result) ? $result : [];
	}

	/**
	 * @param string $varName
	 *
	 * @return bool
	 */
	public static function isVariableANumericVariable($varName)
	{
		return is_numeric(substr($varName, 0, 1));
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableInsideElseCondition(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$nonFunctionTokenTypes = Tokens::$emptyTokens;
		$nonFunctionTokenTypes[] = T_OPEN_PARENTHESIS;
		$nonFunctionTokenTypes[] = T_INLINE_HTML;
		$nonFunctionTokenTypes[] = T_CLOSE_TAG;
		$nonFunctionTokenTypes[] = T_VARIABLE;
		$nonFunctionTokenTypes[] = T_ELLIPSIS;
		$nonFunctionTokenTypes[] = T_COMMA;
		$nonFunctionTokenTypes[] = T_STRING;
		$nonFunctionTokenTypes[] = T_BITWISE_AND;
		$elsePtr = self::getIntOrNull($phpcsFile->findPrevious($nonFunctionTokenTypes, $stackPtr - 1, null, true, null, true));
		$elseTokenTypes = [
			T_ELSE,
			T_ELSEIF,
		];
		if (is_int($elsePtr) && in_array($tokens[$elsePtr]['code'], $elseTokenTypes, true)) {
			return true;
		}
		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableInsideElseBody(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		$conditions = isset($token['conditions']) ? $token['conditions'] : [];
		$elseTokenTypes = [
			T_ELSE,
			T_ELSEIF,
		];
		foreach (array_reverse($conditions, true) as $scopeCode) {
			if (in_array($scopeCode, $elseTokenTypes, true)) {
				return true;
			}
		}

		// Some else body code will not have conditions because it is inline (no
		// curly braces) so we have to look in other ways.
		$previousSemicolonPtr = $phpcsFile->findPrevious([T_SEMICOLON], $stackPtr - 1);
		if (! is_int($previousSemicolonPtr)) {
			$previousSemicolonPtr = 0;
		}
		$elsePtr = $phpcsFile->findPrevious([T_ELSE, T_ELSEIF], $stackPtr - 1, $previousSemicolonPtr);
		if (is_int($elsePtr)) {
			return true;
		}

		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return int[]
	 */
	public static function getAttachedBlockIndicesForElse(File $phpcsFile, $stackPtr)
	{
		$currentElsePtr = $phpcsFile->findPrevious([T_ELSE, T_ELSEIF], $stackPtr - 1);
		if (! is_int($currentElsePtr)) {
			throw new \Exception("Cannot find expected else at {$stackPtr}");
		}

		$ifPtr = $phpcsFile->findPrevious([T_IF], $currentElsePtr - 1);
		if (! is_int($ifPtr)) {
			throw new \Exception("Cannot find if for else at {$stackPtr}");
		}
		$blockIndices = [$ifPtr];

		$previousElseIfPtr = $currentElsePtr;
		do {
			$elseIfPtr = $phpcsFile->findPrevious([T_ELSEIF], $previousElseIfPtr - 1, $ifPtr);
			if (is_int($elseIfPtr)) {
				$blockIndices[] = $elseIfPtr;
				$previousElseIfPtr = $elseIfPtr;
			}
		} while (is_int($elseIfPtr));

		return $blockIndices;
	}

	/**
	 * @param int $needle
	 * @param int $scopeStart
	 * @param int $scopeEnd
	 *
	 * @return bool
	 */
	public static function isIndexInsideScope($needle, $scopeStart, $scopeEnd)
	{
		return ($needle > $scopeStart && $needle < $scopeEnd);
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $scopeStartIndex
	 *
	 * @return int
	 */
	public static function getScopeCloseForScopeOpen(File $phpcsFile, $scopeStartIndex)
	{
		$tokens = $phpcsFile->getTokens();
		$scopeCloserIndex = isset($tokens[$scopeStartIndex]['scope_closer']) ? $tokens[$scopeStartIndex]['scope_closer'] : 0;

		if (self::isArrowFunction($phpcsFile, $scopeStartIndex)) {
			$arrowFunctionInfo = self::getArrowFunctionOpenClose($phpcsFile, $scopeStartIndex);
			$scopeCloserIndex = $arrowFunctionInfo ? $arrowFunctionInfo['scope_closer'] : $scopeCloserIndex;
		}

		if ($scopeStartIndex === 0) {
			$scopeCloserIndex = self::getLastNonEmptyTokenIndexInFile($phpcsFile);
		}
		return $scopeCloserIndex;
	}

	/**
	 * @param File $phpcsFile
	 *
	 * @return int
	 */
	public static function getLastNonEmptyTokenIndexInFile(File $phpcsFile)
	{
		$tokens = $phpcsFile->getTokens();
		foreach (array_reverse($tokens, true) as $index => $token) {
			if (! in_array($token['code'], self::getPossibleEndOfFileTokens(), true)) {
				return $index;
			}
		}
		self::debug('no non-empty token found for end of file');
		return 0;
	}

	/**
	 * @param VariableInfo $varInfo
	 * @param ScopeInfo    $scopeInfo
	 *
	 * @return bool
	 */
	public static function areFollowingArgumentsUsed(VariableInfo $varInfo, ScopeInfo $scopeInfo)
	{
		$foundVarPosition = false;
		foreach ($scopeInfo->variables as $variable) {
			if ($variable === $varInfo) {
				$foundVarPosition = true;
				continue;
			}
			if (! $foundVarPosition) {
				continue;
			}
			if ($variable->scopeType !== ScopeType::PARAM) {
				continue;
			}
			if ($variable->firstRead) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param File         $phpcsFile
	 * @param VariableInfo $varInfo
	 * @param ScopeInfo    $scopeInfo
	 *
	 * @return bool
	 */
	public static function isRequireInScopeAfter(File $phpcsFile, VariableInfo $varInfo, ScopeInfo $scopeInfo)
	{
		$requireTokens = [
			T_REQUIRE,
			T_REQUIRE_ONCE,
			T_INCLUDE,
			T_INCLUDE_ONCE,
		];
		$indexToStartSearch = $varInfo->firstDeclared;
		if (! empty($varInfo->firstInitialized)) {
			$indexToStartSearch = $varInfo->firstInitialized;
		}
		$tokens = $phpcsFile->getTokens();
		$indexToStopSearch = isset($tokens[$scopeInfo->scopeStartIndex]['scope_closer']) ? $tokens[$scopeInfo->scopeStartIndex]['scope_closer'] : null;
		if (! is_int($indexToStartSearch) || ! is_int($indexToStopSearch)) {
			return false;
		}
		$requireTokenIndex = $phpcsFile->findNext($requireTokens, $indexToStartSearch + 1, $indexToStopSearch);
		if (is_int($requireTokenIndex)) {
			return true;
		}
		return false;
	}

	/**
	 * Find the index of the function keyword for a token in a function call's arguments
	 *
	 * For the variable `$foo` in the expression `doSomething($foo)`, this will
	 * return the index of the `doSomething` token.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getFunctionIndexForFunctionCallArgument(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		if (empty($token['nested_parenthesis'])) {
			return null;
		}
		/**
		 * @var array<int|string|null>
		 */
		$startingParenthesis = array_keys($token['nested_parenthesis']);
		$startOfArguments = end($startingParenthesis);
		if (! is_int($startOfArguments)) {
			return null;
		}

		$nonFunctionTokenTypes = Tokens::$emptyTokens;
		$functionPtr = self::getIntOrNull($phpcsFile->findPrevious($nonFunctionTokenTypes, $startOfArguments - 1, null, true, null, true));
		if (! is_int($functionPtr) || ! isset($tokens[$functionPtr]['code'])) {
			return null;
		}
		if (
			$tokens[$functionPtr]['content'] === 'function'
			|| ($tokens[$functionPtr]['content'] === 'fn' && self::isArrowFunction($phpcsFile, $functionPtr))
		) {
			// If there is a function/fn keyword before the beginning of the parens,
			// this is a function definition and not a function call.
			return null;
		}
		if (! empty($tokens[$functionPtr]['scope_opener'])) {
			// If the alleged function name has a scope, this is not a function call.
			return null;
		}

		$functionNameType = $tokens[$functionPtr]['code'];
		if (! in_array($functionNameType, Tokens::$functionNameTokens, true)) {
			// If the alleged function name is not a variable or a string, this is
			// not a function call.
			return null;
		}

		if ($tokens[$functionPtr]['level'] !== $tokens[$stackPtr]['level']) {
			// If the variable is inside a different scope than the function name,
			// the function call doesn't apply to the variable.
			return null;
		}

		return $functionPtr;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableInsideIssetOrEmpty(File $phpcsFile, $stackPtr)
	{
		$functionIndex = self::getFunctionIndexForFunctionCallArgument($phpcsFile, $stackPtr);
		if (! is_int($functionIndex)) {
			return false;
		}
		$tokens = $phpcsFile->getTokens();
		if (! isset($tokens[$functionIndex])) {
			return false;
		}
		$allowedFunctionNames = [
			'isset',
			'empty',
		];
		if (in_array($tokens[$functionIndex]['content'], $allowedFunctionNames, true)) {
			return true;
		}
		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableArrayPushShortcut(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$nonFunctionTokenTypes = Tokens::$emptyTokens;

		$arrayPushOperatorIndex1 = self::getIntOrNull($phpcsFile->findNext($nonFunctionTokenTypes, $stackPtr + 1, null, true, null, true));
		if (! is_int($arrayPushOperatorIndex1)) {
			return false;
		}
		if (! isset($tokens[$arrayPushOperatorIndex1]['content']) || $tokens[$arrayPushOperatorIndex1]['content'] !== '[') {
			return false;
		}

		$arrayPushOperatorIndex2 = self::getIntOrNull($phpcsFile->findNext($nonFunctionTokenTypes, $arrayPushOperatorIndex1 + 1, null, true, null, true));
		if (! is_int($arrayPushOperatorIndex2)) {
			return false;
		}
		if (! isset($tokens[$arrayPushOperatorIndex2]['content']) || $tokens[$arrayPushOperatorIndex2]['content'] !== ']') {
			return false;
		}

		$arrayPushOperatorIndex3 = self::getIntOrNull($phpcsFile->findNext($nonFunctionTokenTypes, $arrayPushOperatorIndex2 + 1, null, true, null, true));
		if (! is_int($arrayPushOperatorIndex3)) {
			return false;
		}
		if (! isset($tokens[$arrayPushOperatorIndex3]['content']) || $tokens[$arrayPushOperatorIndex3]['content'] !== '=') {
			return false;
		}

		return true;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableInsideUnset(File $phpcsFile, $stackPtr)
	{
		$functionIndex = self::getFunctionIndexForFunctionCallArgument($phpcsFile, $stackPtr);
		if (! is_int($functionIndex)) {
			return false;
		}
		$tokens = $phpcsFile->getTokens();
		if (! isset($tokens[$functionIndex])) {
			return false;
		}
		if ($tokens[$functionIndex]['content'] === 'unset') {
			return true;
		}
		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideAssignmentRHS(File $phpcsFile, $stackPtr)
	{
		$previousStatementPtr = $phpcsFile->findPrevious([T_SEMICOLON, T_CLOSE_CURLY_BRACKET, T_OPEN_CURLY_BRACKET, T_COMMA], $stackPtr - 1);
		if (! is_int($previousStatementPtr)) {
			$previousStatementPtr = 1;
		}
		$previousTokenPtr = $phpcsFile->findPrevious([T_EQUAL], $stackPtr - 1, $previousStatementPtr);
		if (is_int($previousTokenPtr)) {
			return true;
		}
		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideAssignmentLHS(File $phpcsFile, $stackPtr)
	{
		// Is the next non-whitespace an assignment?
		$assignPtr = self::getNextAssignPointer($phpcsFile, $stackPtr);
		if (! is_int($assignPtr)) {
			return false;
		}

		// Is this a variable variable? If so, it's not an assignment to the current variable.
		if (self::isTokenVariableVariable($phpcsFile, $stackPtr)) {
			self::debug('found variable variable');
			return false;
		}
		return true;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenVariableVariable(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
		if ($prev === false) {
			return false;
		}
		if ($tokens[$prev]['code'] === T_DOLLAR) {
			return true;
		}
		if ($tokens[$prev]['code'] !== T_OPEN_CURLY_BRACKET) {
			return false;
		}

		$prevPrev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prev - 1), null, true);
		if ($prevPrev !== false && $tokens[$prevPrev]['code'] === T_DOLLAR) {
			return true;
		}
		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ForLoopInfo
	 */
	public static function makeForLoopInfo(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		$forIndex = $stackPtr;
		$blockStart = $token['parenthesis_closer'];
		if (isset($token['scope_opener'])) {
			$blockStart = $token['scope_opener'];
			$blockEnd = $token['scope_closer'];
		} else {
			// Some for loop blocks will not have scope positions because it they are
			// inline (no curly braces) so we have to find the end of their scope by
			// looking for the end of the next statement.
			$nextSemicolonIndex = $phpcsFile->findNext([T_SEMICOLON], $token['parenthesis_closer']);
			if (! is_int($nextSemicolonIndex)) {
				$nextSemicolonIndex = $token['parenthesis_closer'] + 1;
			}
			$blockEnd = $nextSemicolonIndex;
		}
		$initStart = intval($token['parenthesis_opener']) + 1;
		$initEnd = null;
		$conditionStart = null;
		$conditionEnd = null;
		$incrementStart = null;
		$incrementEnd = $token['parenthesis_closer'] - 1;

		$semicolonCount = 0;
		$forLoopLevel = $tokens[$forIndex]['level'];
		$forLoopNestedParensCount = 1;

		if (isset($tokens[$forIndex]['nested_parenthesis'])) {
			$forLoopNestedParensCount = count($tokens[$forIndex]['nested_parenthesis']) + 1;
		}

		for ($i = $initStart; ($i <= $incrementEnd && $semicolonCount < 2); $i++) {
			if ($tokens[$i]['code'] !== T_SEMICOLON) {
				continue;
			}

			if ($tokens[$i]['level'] !== $forLoopLevel) {
				continue;
			}

			if (count($tokens[$i]['nested_parenthesis']) !== $forLoopNestedParensCount) {
				continue;
			}

			switch ($semicolonCount) {
				case 0:
					$initEnd = $i;
					$conditionStart = $initEnd + 1;
					break;
				case 1:
					$conditionEnd = $i;
					$incrementStart = $conditionEnd + 1;
					break;
			}
			$semicolonCount += 1;
		}

		if ($initEnd === null || $conditionStart === null || $conditionEnd === null || $incrementStart === null) {
			throw new \Exception("Cannot parse for loop at position {$forIndex}");
		}

		return new ForLoopInfo(
			$forIndex,
			$blockStart,
			$blockEnd,
			$initStart,
			$initEnd,
			$conditionStart,
			$conditionEnd,
			$incrementStart,
			$incrementEnd
		);
	}

	/**
	 * @param int                     $stackPtr
	 * @param array<int, ForLoopInfo> $forLoops
	 * @return ForLoopInfo|null
	 */
	public static function getForLoopForIncrementVariable($stackPtr, $forLoops)
	{
		foreach ($forLoops as $forLoop) {
			if ($stackPtr > $forLoop->incrementStart && $stackPtr < $forLoop->incrementEnd) {
				return $forLoop;
			}
		}
		return null;
	}

	/**
	 * Return true if the token looks like constructor promotion.
	 *
	 * Call on a parameter variable token only.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isConstructorPromotion(File $phpcsFile, $stackPtr)
	{
		$functionIndex = self::getFunctionIndexForFunctionParameter($phpcsFile, $stackPtr);
		if (! $functionIndex) {
			return false;
		}

		$tokens = $phpcsFile->getTokens();

		// If the previous token is a visibility keyword, this is constructor
		// promotion. eg: `public $foobar`.
		$prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), $functionIndex, true);
		if (! is_int($prev)) {
			return false;
		}
		$prevToken = $tokens[$prev];
		if (in_array($prevToken['code'], Tokens::$scopeModifiers, true)) {
			return true;
		}

		// If the previous token is not a visibility keyword, but the one before it
		// is, the previous token was probably a typehint and this is constructor
		// promotion. eg: `public boolean $foobar`.
		$prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prev - 1), $functionIndex, true);
		if (! is_int($prev)) {
			return false;
		}
		$prevToken = $tokens[$prev];
		if (in_array($prevToken['code'], Tokens::$scopeModifiers, true)) {
			return true;
		}

		return false;
	}

	/**
	 * Return true if the token is inside an abstract class.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isInAbstractClass(File $phpcsFile, $stackPtr)
	{
		$classIndex = $phpcsFile->getCondition($stackPtr, T_CLASS);
		if (! is_int($classIndex)) {
			return false;
		}
		$classProperties = $phpcsFile->getClassProperties($classIndex);
		return $classProperties['is_abstract'];
	}

	/**
	 * Return true if the function body is empty or contains only `return;`
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr  The index of the function keyword.
	 *
	 * @return bool
	 */
	public static function isFunctionBodyEmpty(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		if ($tokens[$stackPtr]['code'] !== T_FUNCTION) {
			return false;
		}
		$functionScopeStart = $tokens[$stackPtr]['scope_opener'];
		$functionScopeEnd = $tokens[$stackPtr]['scope_closer'];
		$tokensToIgnore = array_merge(
			Tokens::$emptyTokens,
			[
				T_RETURN,
				T_SEMICOLON,
				T_OPEN_CURLY_BRACKET,
				T_CLOSE_CURLY_BRACKET,
			]
		);
		for ($i = $functionScopeStart; $i < $functionScopeEnd; $i++) {
			if (! in_array($tokens[$i]['code'], $tokensToIgnore, true)) {
				return false;
			}
		}
		return true;
	}
}
