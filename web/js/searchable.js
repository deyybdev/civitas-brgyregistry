// Searchable dropdown.
// Turns every <select data-search> into a type-to-search field.
// The real <select> stays in the page (hidden), so the forms and PHP work the same.
(function () {
    var MAX_SHOWN = 100;   // show at most this many matches, so very long lists stay fast
    var counter = 0;       // makes a unique id for each list

    // Lowercase and remove accents, so "quinones" finds "Quiñones".
    function clean(text) {
        return text.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase();
    }

    function makeSearchable(select) {
        var isRequired = select.required;
        var options = [];       // every choice: value, label, and cleaned label for searching
        var placeholder = "";   // hint text such as "Select resident"

        Array.prototype.forEach.call(select.options, function (opt) {
            var label = opt.textContent.replace(/\s+/g, " ").trim();
            if (opt.value === "" && isRequired) {
                placeholder = label;   // "Select resident" is a hint, not a real choice
                return;
            }
            options.push({ value: opt.value, label: label, search: clean(label) });
        });

        var shown = [];      // the choices currently listed
        var active = -1;     // the highlighted choice (index in shown)
        var isOpen = false;
        var listId = "ss-list-" + (++counter);

        // ----- Build the new field -----
        var wrap = document.createElement("div");
        wrap.className = "ss";

        var input = document.createElement("input");
        input.type = "text";
        input.className = "ss-input";
        if (select.id) {
            input.id = select.id;   // the label points to this box now
        }
        input.placeholder = placeholder;
        input.autocomplete = "off";
        input.spellcheck = false;
        input.setAttribute("role", "combobox");
        input.setAttribute("aria-autocomplete", "list");
        input.setAttribute("aria-expanded", "false");
        input.setAttribute("aria-controls", listId);
        if (select.getAttribute("aria-label")) {
            input.setAttribute("aria-label", select.getAttribute("aria-label"));
        }

        var arrow = document.createElement("span");
        arrow.className = "ss-arrow";
        arrow.innerHTML = '<svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

        var list = document.createElement("ul");
        list.className = "ss-list";
        list.id = listId;
        list.setAttribute("role", "listbox");
        list.hidden = true;

        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(input);
        wrap.appendChild(arrow);
        wrap.appendChild(list);
        wrap.appendChild(select);   // the real select moves inside and is hidden by CSS

        select.removeAttribute("id");
        select.required = false;    // the visible box checks this instead (see updateValidity)
        select.tabIndex = -1;
        select.setAttribute("aria-hidden", "true");

        // ----- Helpers -----

        // The label of the option that is selected now ("" when nothing is chosen).
        function selectedLabel() {
            for (var i = 0; i < options.length; i++) {
                if (options[i].value === select.value) {
                    return options[i].label;
                }
            }
            return "";
        }

        // Required fields show a message when nothing is chosen.
        function updateValidity() {
            if (isRequired && selectedLabel() === "") {
                input.setCustomValidity("Please select an item from the list.");
            } else {
                input.setCustomValidity("");
            }
        }

        // Put the chosen label back in the box (used after closing the list).
        function showSelected() {
            input.value = selectedLabel();
            updateValidity();
        }

        // Highlight one choice and scroll to it.
        function setActive(index) {
            var items = list.querySelectorAll("li[data-i]");
            if (items[active]) {
                items[active].classList.remove("on");
            }
            active = index;
            if (items[active]) {
                items[active].classList.add("on");
                items[active].scrollIntoView({ block: "nearest" });
                input.setAttribute("aria-activedescendant", items[active].id);
            }
        }

        // Build the list for what the person typed. Every word must match, in any order.
        function render(query) {
            var words = clean(query).split(/[\s,]+/).filter(Boolean);
            var matches = options.filter(function (o) {
                return words.every(function (w) { return o.search.indexOf(w) !== -1; });
            });
            shown = matches.slice(0, MAX_SHOWN);
            list.innerHTML = "";
            active = -1;

            shown.forEach(function (o, i) {
                var li = document.createElement("li");
                li.id = listId + "-" + i;
                li.setAttribute("role", "option");
                li.setAttribute("data-i", i);
                li.textContent = o.label;
                if (o.value === select.value) {
                    li.setAttribute("aria-selected", "true");
                }
                list.appendChild(li);
            });

            if (shown.length === 0) {
                var empty = document.createElement("li");
                empty.className = "ss-note";
                empty.textContent = "No match";
                list.appendChild(empty);
            } else if (matches.length > MAX_SHOWN) {
                var more = document.createElement("li");
                more.className = "ss-note";
                more.textContent = "Showing the first " + MAX_SHOWN + " of " + matches.length + ". Keep typing to narrow.";
                list.appendChild(more);
            }

            // Start on the chosen item if it is listed, otherwise on the first match.
            var start = 0;
            if (query === "") {
                shown.forEach(function (o, i) {
                    if (o.value === select.value) { start = i; }
                });
            }
            if (shown.length > 0) {
                setActive(start);
            }
        }

        function open() {
            if (isOpen) { return; }
            isOpen = true;
            list.hidden = false;
            input.setAttribute("aria-expanded", "true");
            render("");
            input.select();   // so typing replaces the old text
        }

        function close() {
            isOpen = false;
            list.hidden = true;
            input.setAttribute("aria-expanded", "false");
            input.removeAttribute("aria-activedescendant");
            showSelected();
        }

        // Use one choice: set the real select and tell the page it changed.
        function choose(index) {
            var option = shown[index];
            if (!option) { return; }
            select.value = option.value;
            select.dispatchEvent(new Event("change", { bubbles: true }));
            close();
        }

        // ----- Events -----
        input.addEventListener("focus", open);
        input.addEventListener("click", function () {
            open();            // opens again after Escape
            input.select();    // a click should not leave the cursor inside the old text
        });

        input.addEventListener("input", function () {
            if (!isOpen) {
                isOpen = true;
                list.hidden = false;
                input.setAttribute("aria-expanded", "true");
            }
            render(input.value);
        });

        input.addEventListener("keydown", function (e) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                if (!isOpen) { open(); } else if (active < shown.length - 1) { setActive(active + 1); }
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                if (!isOpen) { open(); } else if (active > 0) { setActive(active - 1); }
            } else if (e.key === "Enter" && isOpen) {
                e.preventDefault();   // do not submit the form while picking
                choose(active);
            } else if (e.key === "Escape" && isOpen) {
                e.preventDefault();
                close();
            } else if (e.key === "Tab") {
                close();
            }
        });

        input.addEventListener("blur", close);

        // Keep the focus in the box while clicking inside the list.
        list.addEventListener("mousedown", function (e) { e.preventDefault(); });

        list.addEventListener("click", function (e) {
            var li = e.target.closest("li[data-i]");
            if (li) { choose(parseInt(li.getAttribute("data-i"), 10)); }
        });

        list.addEventListener("mouseover", function (e) {
            var li = e.target.closest("li[data-i]");
            if (li) { setActive(parseInt(li.getAttribute("data-i"), 10)); }
        });

        // A form reset puts the real select back, so refresh the box.
        if (select.form) {
            select.form.addEventListener("reset", function () {
                setTimeout(showSelected, 0);
            });
        }

        showSelected();
    }

    document.querySelectorAll("select[data-search]").forEach(makeSearchable);
})();