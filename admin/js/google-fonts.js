const API_URL = "https://google-fonts-service-proxy.vercel.app/api"; // We are using this proxy to avoid sharing Google Fonst API key: https://github.com/matiasbenedetto/google-fonts-service-proxy
const DEMO_TEXT = "The quick brown fox jumps over the lazy dog";
let fonts = [];
let fontSelected = null;
let variantsSelected = {};

async function get_google_fonts() {
    try { // Try to get the font list from the API
        const response = await fetch(API_URL);
        const { items } = await response.json();
        if ( !items ) {
            throw new Error('API response is invalid');
        }
        return items;
    } catch (error) { // If the API is not available, use the local list
        const currentUrl = new URL(document.getElementById('google-fonts-script-js').src);
        const fallbackURL = currentUrl.origin + currentUrl.pathname.replace('admin/js/google-fonts.js', 'assets/google-fonts/fallback-fonts-list.json');
        const response = await fetch(fallbackURL);
        const { items } = await response.json();
        return items;
    }
}

function prepareSelectElement () {
    const selectElement = document.getElementById('google-font-id');
    selectElement.addEventListener('change', onGoogleFontNameChange);
}

async function fillFontSelect() {
    fonts = await get_google_fonts();
    const selectElement = document.getElementById('google-font-id');
    for (const i in fonts) {
        const font = fonts[i];
        const opt = document.createElement("option");
        opt.value = i;
        opt.innerHTML = font['family'];
        selectElement.appendChild(opt);
    }
}

function onGoogleFontNameChange() {
    const fontNameElement = document.getElementById("font-name");
    const fontsTableElement = document.getElementById("google-fonts-table");
    const hintElements = document.querySelector('.hint');

    if(this.value) {
        fontNameElement.value = fonts[this.value]['family'];
        fontSelected = fonts[this.value];
        fontsTableElement.style.display = "block";
        hintElements.style.display = "block";
    } else {
        fontNameElement.value = "";
        fontSelected = null;
        fontsTableElement.style.display = "none";
        hintElements.style.display = "none";
    }

    emptyFontOptions();
    displayFontOptions();
}

function displayFontOptions () {
    const fontOptionsElement = document.getElementById('font-options');

    for ( const variant of fontSelected.variants ) {
        // Loads the selected font to create the previews
        const style = variant.includes('italic') ? 'italic' : 'normal';
        const weight = variant === 'regular' || variant === 'italic' ? '400' : variant.replace('italic', '');
        const newFont = new FontFace(fontSelected['family'], `url(${fontSelected['files'][variant]})`, { style: style, weight: weight });
        newFont.load().then(function(loaded_face) {
            document.fonts.add(loaded_face);
        }).catch(function(error) {
            console.error(error);
        });

        // Creates the font variant elements and adds them to the page
        const tr = document.createElement("tr");
        const td1 = document.createElement("td");
        const td2 = document.createElement("td");
        const td3 = document.createElement("td");

        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.id = variant;
        checkbox.name = variant;
        checkbox.addEventListener('change', onFontVariantChange);
        td1.appendChild(checkbox);
        td2.innerHTML = variant;

        const paragraph = document.createElement("p");
        paragraph.style.fontFamily = fontSelected['family'];
        paragraph.style.fontStyle = style;
        paragraph.style.fontWeight = weight;
        paragraph.innerText = `${DEMO_TEXT}`;
        td3.appendChild(paragraph);

        tr.appendChild(td1);
        tr.appendChild(td2);
        tr.appendChild(td3);

        fontOptionsElement.appendChild(tr);
    }
}

function onFontVariantChange () {
    const googleFontsSelectedElement = document.getElementById('google-font-variants');
    const submitElement = document.getElementById('google-fonts-submit');
    if (this.checked) {
        variantsSelected[this.id] = fontSelected['files'][this.id];
    } else {
        delete variantsSelected[this.id];
    }
    googleFontsSelectedElement.value = Object.keys(variantsSelected).map(key => `${key}::${variantsSelected[key]}`).join(',');
    submitElement.disabled = !googleFontsSelectedElement.value;
}

function emptyFontOptions () {
    const fontOptionsElement = document.getElementById('font-options');
    fontOptionsElement.innerHTML = "";
    const googleFontsSelectedElement = document.getElementById('google-font-variants');
    googleFontsSelectedElement.value = "";
    const submitElement = document.getElementById('google-fonts-submit');
    submitElement.disabled = true;
    variantsSelected = {};
}

function init () {
    fillFontSelect();
    prepareSelectElement();
}

init();
