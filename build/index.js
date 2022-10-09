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
    demoText
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
    variant: "secondary"
  }, "Edit")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    variant: "tertiary",
    isDestructive: true
  }, "Remove")));
}

/* harmony default export */ __webpack_exports__["default"] = (FontFace);

/***/ }),

/***/ "./src/manage-fonts.js":
/*!*****************************!*\
  !*** ./src/manage-fonts.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _font_face__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./font-face */ "./src/font-face.js");


const DEMO_TEXT = "The quick brown fox jumps over the lazy dog";

function ManageFonts() {
  const themeFontsJsonElement = document.querySelector("#theme-fonts-json");
  const themeFontsJsonValue = themeFontsJsonElement.value;
  const themeFontsJson = JSON.parse(themeFontsJsonValue);
  const [newThemeFonts, setNewThemeFonts] = themeFontsJson;
  console.log(themeFontsJson);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "font-families"
  }, themeFontsJson.map(font => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "wp-list-table widefat table-view-list"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Font Family: ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, font.fontFamily), " | Slug: ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, font.slug))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "font-family-contents"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "wp-list-table widefat striped table-view-list"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Style"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Weight"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Preview"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Edit"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, "Remove")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, font.fontFace.map(fontFace => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_font_face__WEBPACK_IMPORTED_MODULE_1__["default"], {
    fontFace: fontFace,
    demoText: DEMO_TEXT
  })))))))));
}

/* harmony default export */ __webpack_exports__["default"] = (ManageFonts);

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