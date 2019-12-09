/**
 * References:
 * https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent
 */

//-----------------------------------------------------------------------------
// Keyboard shortcut handling

var keymap = [];
var remapping = {
    active: false,
    index: 0
};

window.onkeyup = function(e) {
    if (remapping.active) {
        remapFinish(remapping.index, eventToKeyCode(e));
        e.preventDefault();
        return;
    }
};

window.onkeydown = function(e) {
    if (remapping.active) {
        e.preventDefault();
        return;
    }

    //Disable hotkeys in the menu so we don't accidentally press anything
    if (isSettingsVisible()) {
        return;
    }

    if (e.target.tagName === "TEXTAREA" || (e.target.tagName === "INPUT" && e.target.type !== "checkbox") || e.target.tagName === "SELECT") return; // disable keyboard event when typing to textarea/input

    var codeName = eventToKeyCode(e);

    for (var i = 0; i < keymap.length; i++) {
        if (keymap[i].code === codeName) {
            keymap[i].fn(e, keymap[i].options);
        }
    }
};

/**
 * Register a function to be called when a key is pressed.
 * @param {object} parameters Parameters object, contains:
 *     {string} name - Display name of the action
 *     {string} code Keycode, e.g. "KeyA" or "ArrowUp" or "Ctrl KeyR", see KeyboardEvent.code
 *     Note the alphabetical order of modifier keys: Alt Control Meta Shift
 * @param {Function} fn Function / callable
 */
function registerKeyHandler(parameters, fn) {
    parameters.originalCode = parameters.code;
    parameters.fn = fn;

    //Check local storage
    if (remapGetLS(parameters.name) !== null) {
        parameters.code = remapGetLS(parameters.name);
    }

    keymap.push(parameters);
}

/**
 * Unregister a key handler. Arguments are equivalent to registerKeyHandler()
 * @param code Keycode, see registerKeyHandler()
 * @param fn Function / callable
 */
function unregisterKeyHandler(code, fn) {
    for (var i = 0; i < keymap.length; i++) {
        if (keymap[i].code === code || keymap[i].fn === fn) {
            //Delete 1 at i
            keymap.splice(i, 1);
            i--;
        }
    }
}

function isSettingsVisible() {
    return $("#settings-popup").is(":visible");
}

function showSettings() {
    generateHotkeysList();
    $("#settings-popup").show();
}

function hideSettings() {
    if (remapping.active) {
        return;
    }

    $('#settings-popup').hide();
}

Twig.twig({
    id: "HotkeyList",
    href: "/templates/grading/settings/HotkeyList.twig",
    async: true
});

/**
 * Generate list of hotkeys on the ui
 */
function generateHotkeysList() {
    var parent = $("#hotkeys-list");

    parent.replaceWith(Twig.twig({
        ref: "HotkeyList"
    }).render({
        keymap: keymap
    }));
}

/**
 * Start rebinding a hotkey
 * @param {int} i Index of hotkey to rebind
 */
function remapHotkey(i) {
    if (remapping.active) {
        return;
    }

    var button = $("#remap-" + i);
    button.text("Enter Key...");
    remapping.active = true;
    remapping.index = i;

    $(".remap-disable").attr("disabled", "disabled");
    $("#settings-close").attr("disabled", "disabled");
    button.attr("disabled", null);
    button.addClass("btn-success");
}

/**
 * Called when remapping has finished and should save (or discard) a pressed key
 * @param {int} index Index of the hotkey
 * @param {string} code New keycode for the hotkey
 */
function remapFinish(index, code) {
    //Check if code is already used
    for (var i = 0; i < keymap.length; i++) {
        if (index === i) {
            continue;
        }
        if (keymap[i].code === code) {
            //Oh no
            var button = $("#remap-" + index);
            button.text("Enter Unique Key...");
            button.addClass("btn-danger");
            button.removeClass("btn-success");
            return;
        }
    }

    keymap[index].code = code;
    remapSetLS(keymap[index].name, code);

    remapping.active = false;
    generateHotkeysList();

    $(".remap-disable").attr("disabled", null);
    $("#settings-close").attr("disabled", null);
}

/**
 * Revert a hotkey to its original code
 * @param {int} i Index of hotkey
 */
function remapUnset(i) {
    remapFinish(i, keymap[i].originalCode);
}

/**
 * Get the keycode for a hotkey binding from localStorage
 * @param {string} mapName Name of hotkey binding
 * @returns {string|null} Code value if exists, else null
 */
function remapGetLS(mapName) {
    var lsKeymap = localStorage.getItem("keymap");
    if (lsKeymap === null) {
        return null;
    }
    try {
        lsKeymap = JSON.parse(lsKeymap);
    } catch (e) {
        return null;
    }
    if (!lsKeymap.hasOwnProperty(mapName)) {
        return null;
    }
    return lsKeymap[mapName].code;
}

/**
 * Save a hotkey binding to localStorage
 * @param {string} mapName Name of hotkey binding
 * @param {string} code New binding code
 */
function remapSetLS(mapName, code) {
    var lsKeymap = localStorage.getItem("keymap");
    if (lsKeymap === null) {
        lsKeymap = {};
    }
    else {
        try {
            lsKeymap = JSON.parse(lsKeymap);
        } catch (e) {
            lsKeymap = {};
        }
    }
    if (!lsKeymap.hasOwnProperty(mapName)) {
        lsKeymap[mapName] = {};
    }
    lsKeymap[mapName].code = code;

    localStorage.setItem("keymap", JSON.stringify(lsKeymap));
}

/**
 * Convert a KeyboardEvent into a (generally) user-readable string
 * @param {KeyboardEvent} e Event
 * @returns {string} String form of the event
 */
function eventToKeyCode(e) {
    var codeName = e.code;

    //Apply modifiers to code name in reverse alphabetical order so they come out alphabetical
    if (e.shiftKey && (e.code !== "ShiftLeft" && e.code !== "ShiftRight")) {
        codeName = "Shift " + codeName;
    }
    if (e.metaKey && (e.code !== "MetaLeft" && e.code !== "MetaRight")) {
        codeName = "Meta " + codeName;
    }
    if (e.ctrlKey && (e.code !== "ControlLeft" && e.code !== "ControlRight")) {
        codeName = "Control " + codeName;
    }
    if (e.altKey && (e.code !== "AltLeft" && e.code !== "AltRight")) {
        codeName = "Alt " + codeName;
    }

    return codeName;
}
