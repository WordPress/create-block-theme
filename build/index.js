/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/font-face.js":
/*!**************************!*\
  !*** ./src/font-face.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);



function FontFace(_ref) {
  let {
    fontFace,
    demoText,
    deleteFontFace
  } = _ref;
  const demoStyles = {
    fontFamily: fontFace.fontFamily,
    fontStyle: fontFace.fontStyle,
    fontWeight: fontFace.fontWeight
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
    className: "font-face"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, fontFace.fontStyle), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, fontFace.fontWeight), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    className: "demo-cell"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    style: demoStyles
  }, demoText)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    variant: "tertiary",
    isDestructive: true,
    onClick: deleteFontFace
  }, "Remove")));
}

/* harmony default export */ __webpack_exports__["default"] = (FontFace);

/***/ }),

/***/ "./src/font-family.js":
/*!****************************!*\
  !*** ./src/font-family.js ***!
  \****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _font_face__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./font-face */ "./src/font-face.js");




function FontFamily(_ref) {
  let {
    fontFamily,
    fontFamilyIndex,
    deleteFontFamily,
    deleteFontFace,
    demoText
  } = _ref;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "wp-list-table widefat table-view-list"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    class: "font-family-head"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, fontFamily.fontFamily, ":"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    variant: "tertiary",
    isDestructive: true,
    onClick: () => deleteFontFamily(fontFamilyIndex)
  }, "Remove Font Family")))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "font-family-contents"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "wp-list-table widefat striped table-view-list"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Style"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Weight"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Preview"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, fontFamily.fontFace.map((fontFace, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_font_face__WEBPACK_IMPORTED_MODULE_2__["default"], {
    fontFace: fontFace,
    fontFamilyIndex: fontFamilyIndex,
    fontFaceIndex: i,
    demoText: demoText,
    key: `fontface${i}`,
    deleteFontFace: () => deleteFontFace(fontFamilyIndex, i)
  })))))));
}

FontFamily.defaultProps = {
  demoText: "The quick brown fox jumps over the lazy dog."
};
/* harmony default export */ __webpack_exports__["default"] = (FontFamily);

/***/ }),

/***/ "./src/manage-fonts.js":
/*!*****************************!*\
  !*** ./src/manage-fonts.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _font_family__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./font-family */ "./src/font-family.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);





function ManageFonts() {
  var _newThemeFonts$fontTo;

  const themeFontsJsonElement = document.querySelector("#theme-fonts-json");
  const manageFontsFormElement = document.querySelector("#manage-fonts-form");
  const themeFontsJsonValue = themeFontsJsonElement.value;
  const themeFontsJson = JSON.parse(themeFontsJsonValue);
  const [newThemeFonts, setNewThemeFonts] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(themeFontsJson);
  const [showConfirmDialog, setShowConfirmDialog] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [fontToDelete, setFontToDelete] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)({});

  function requestDeleteConfirmation(fontFamilyIndex, fontFaceIndex) {
    setFontToDelete({
      fontFamilyIndex,
      fontFaceIndex
    }, setShowConfirmDialog(true));
  }

  function confirmDelete() {
    if (fontToDelete.fontFamilyIndex !== undefined && fontToDelete.fontFaceIndex !== undefined) {
      deleteFontFace(fontToDelete.fontFamilyIndex, fontToDelete.fontFaceIndex);
    } else {
      deleteFontFamily(fontToDelete.fontFamilyIndex);
    }

    if (fontToDelete.fontFamilyIndex !== undefined || fontToDelete.fontFaceIndex !== undefined) {
      setTimeout(() => {
        manageFontsFormElement.submit();
      }, 0);
    }

    setFontToDelete({});
    setShowConfirmDialog(false);
  }

  function cancelDelete() {
    setFontToDelete({});
    setShowConfirmDialog(false);
  }

  function deleteFontFamily(fontFamilyIndex) {
    const updatedFonts = newThemeFonts.filter((_, index) => index !== fontFamilyIndex);
    setNewThemeFonts(updatedFonts);
  }

  function deleteFontFace() {
    const {
      fontFamilyIndex,
      fontFaceIndex
    } = fontToDelete;
    const updatedFonts = newThemeFonts.reduce((acc, fontFamily, index) => {
      if (index === fontFamilyIndex && fontFamily.fontFace.length > 1) {
        const {
          fontFace,
          ...updatedFontFamily
        } = fontFamily;
        updatedFontFamily.fontFace = fontFamily.fontFace.filter((_, index) => index !== fontFaceIndex);
        return [...acc, updatedFontFamily];
      }

      if (fontFamily.fontFace.length == 1) {
        return acc;
      }

      return [...acc, fontFamily];
    }, []);
    setNewThemeFonts(updatedFonts);
  }

  const fontFamilyToDelete = newThemeFonts[fontToDelete.fontFamilyIndex];
  const fontFaceToDelete = (_newThemeFonts$fontTo = newThemeFonts[fontToDelete.fontFamilyIndex]) === null || _newThemeFonts$fontTo === void 0 ? void 0 : _newThemeFonts$fontTo.fontFace[fontToDelete.fontFaceIndex];
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => {
      console.log(newThemeFonts);
      manageFontsFormElement.submit();
    }
  }, "Update"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "input",
    name: "new-theme-fonts-json",
    value: JSON.stringify(newThemeFonts)
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalConfirmDialog, {
    isOpen: showConfirmDialog,
    onConfirm: confirmDelete,
    onCancel: cancelDelete
  }, (fontToDelete === null || fontToDelete === void 0 ? void 0 : fontToDelete.fontFamilyIndex) !== undefined && (fontToDelete === null || fontToDelete === void 0 ? void 0 : fontToDelete.fontFaceIndex) !== undefined ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Are you sure you want to delete \"", fontFaceToDelete === null || fontFaceToDelete === void 0 ? void 0 : fontFaceToDelete.fontStyle, " - ", fontFaceToDelete === null || fontFaceToDelete === void 0 ? void 0 : fontFaceToDelete.fontWeight, "\"  variant of \"", fontFamilyToDelete === null || fontFamilyToDelete === void 0 ? void 0 : fontFamilyToDelete.fontFamily, "\" from your theme?") : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Are you sure you want to delete \"", fontFamilyToDelete === null || fontFamilyToDelete === void 0 ? void 0 : fontFamilyToDelete.fontFamily, "\" from your theme?"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "This action will delete the font definition and the font file assets from your theme.")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "font-families"
  }, newThemeFonts.map((fontFamily, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_font_family__WEBPACK_IMPORTED_MODULE_2__["default"], {
    fontFamily: fontFamily,
    fontFamilyIndex: i,
    key: `fontfamily${i}`,
    deleteFontFamily: requestDeleteConfirmation,
    deleteFontFace: requestDeleteConfirmation
  }))));
}

/* harmony default export */ __webpack_exports__["default"] = (ManageFonts);

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ (function(module) {

module.exports = window["React"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ (function(module) {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

module.exports = window["wp"]["element"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _manage_fonts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./manage-fonts */ "./src/manage-fonts.js");




function App() {
  const params = new URLSearchParams(document.location.search);
  let page = params.get("page");

  switch (page) {
    case "manage-fonts":
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_manage_fonts__WEBPACK_IMPORTED_MODULE_1__["default"], null);

    default:
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Default");
  }
}

window.addEventListener('load', function () {
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.render)((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(App, null), document.querySelector('#manage-fonts'));
}, false);
}();
/******/ })()
;
//# sourceMappingURL=index.js.map