import {Controller} from "@hotwired/stimulus";

const STORAGE_KEY = "payment_order_templates";

export default class extends Controller {

    static targets = ['menu', 'delete_menu', 'new_name'];
    static values = {transNotEmpty: String, transDeleteConfirm: String}


    _templates = [];

    connect()
    {
        this._loadTemplatesFromStorage();
        this._updateMenu();
    }

    _loadTemplatesFromStorage()
    {
        const encodedTemplates = localStorage.getItem(STORAGE_KEY);
        if (encodedTemplates !== null) {
            this._templates = JSON.parse(encodedTemplates);
        } else {
            this._templates = [];
        }

        console.debug("Found templates: ", this._templates.length);

        //Try to migrate old templates to new system
        for (const template of this._templates) {
            //Merge first and last name into the new submitter_name field
            if (template['first_name'] || template['last_name']) {
                template['submitter_name'] = `${template['first_name']} ${template['last_name']}`;
                delete template['first_name'];
                delete template['last_name'];
            }
        }
    }


    _saveTemplatesToStorage()
    {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this._templates));
    }

    /**
     * Serialize the current form into a JSON object
     * @return {{}}
     * @private
     */
    _serializeForm()
    {
        let data = {};
        let inputs = this.element.querySelectorAll('[data-json]');
        for (let input of inputs) {
            //Synchronize tomselect if existing on this element
            if (input.tomselect) {
                input.tomselect.sync();
            }

            //Only add the input to the data if it has a value
            if (input.value) {
                data[input.dataset.json] = input.value;
            }
        }

        return data;
    }

    /**
     * Deserialize the given object and fill the current form with the data
     * @param data
     */
    deserializeForm(data)
    {
        for (const [key, value] of Object.entries(data)) {
            let input = this.element.querySelector(`[data-json="${key}"]`);
            if (input) {
                input.value = value;

                //Synchronize tomselect if existing on this element
                if (input.tomselect) {
                    input.tomselect.sync();
                }
            }
        }
    }

    /**
     * Save the current form content as a template in the browser local storage
     */
    saveTemplate()
    {
        let name = this.new_nameTarget.value.trim();
        if (!name) {
            alert(this.transNotEmptyValue);
            return;
        }

        let data = this._serializeForm();
        this._templates.push({name, data});
        this._saveTemplatesToStorage();
        this._updateMenu();

        this.new_nameTarget.value = "";
    }

    /**
     * Export the current form content as a JSON file, which gets downloaded
     */
    exportTemplate()
    {
        let name = this.new_nameTarget.value.trim();
        if (!name) {
            alert(this.transNotEmptyValue);
            return;
        }

        let data = this._serializeForm();
        let blob = new Blob([JSON.stringify(data)], {type: 'application/json'});
        let url = URL.createObjectURL(blob);

        let a = document.createElement('a');
        a.href = url;
        a.download = name + ".json";
        a.click();
    }

    _updateMenu()
    {
        [this.menuTarget, this.delete_menuTarget].forEach((menu) => {
            /** @type {HTMLElement} menu */

            //Clear the menu
            menu.innerHTML = "";
            for (const template of this._templates) {
                const item = document.createElement('button');
                item.textContent = template.name;
                item.classList.add('dropdown-item');

                //The menu selector allows to load a template, the delete menu allows to delete a template
                if (menu === this.menuTarget) {
                    item.addEventListener('click', () => {
                        this.deserializeForm(template.data);
                    });
                } else if (menu === this.delete_menuTarget) {
                    item.addEventListener('click', () => {
                        this._removeTemplate(template);
                    });
                }

                menu.appendChild(item);
            }
        });
    }

    _removeTemplate(indexOrData) {
        if (!confirm(this.transDeleteConfirmValue)) {
            return;
        }

        let index = indexOrData;
        if (typeof indexOrData === 'object') {
            index = this._templates.indexOf(indexOrData);
        }

        if (index !== -1) {
            this._templates.splice(index, 1);
            this._saveTemplatesToStorage();
            this._updateMenu();
        }
    }

    importJSON() {
        //Create a file selector
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';

        input.addEventListener('change', (event) => {
            const file = document.getElementById('fileSelect').files[0];
            const reader = new FileReader();
            reader.onload = (e) => {
                const content = e.target.result;
                this.deserializeForm(JSON.parse(content));
            }
            reader.readAsText(file);
        });

        input.click();
    }
}