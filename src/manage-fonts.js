import FontFace from "./font-face";

const DEMO_TEXT = "The quick brown fox jumps over the lazy dog";

function ManageFonts () {
    const themeFontsJsonElement = document.querySelector("#theme-fonts-json");
    const themeFontsJsonValue = themeFontsJsonElement.value;
    const themeFontsJson = JSON.parse(themeFontsJsonValue);
    const [newThemeFonts, setNewThemeFonts] =  themeFontsJson;
    console.log(themeFontsJson);
    return (
        <div className="font-families">
            {themeFontsJson.map((font) => (
                <table className="wp-list-table widefat table-view-list">
                    <thead>
                        <td>Font Family: <strong>{font.fontFamily}</strong> | Slug: <strong>{font.slug}</strong></td>
                    </thead>
                    <tbody>
                        <div className="font-family-contents">
                            <table className="wp-list-table widefat striped table-view-list">
                                <thead>
                                    <td>Style</td>
                                    <td>Weight</td>
                                    <td>Preview</td>
                                    <td>Edit</td>
                                    <td>Remove</td>
                                </thead>
                                <tbody>
                                    {font.fontFace.map(fontFace => (
                                        <FontFace fontFace={fontFace} demoText={DEMO_TEXT} />
                                    ))}  
                                </tbody>  
                            </table>
                        </div>
                    </tbody>
                </table>
            ))}
        </div>
    );
}

export default ManageFonts;