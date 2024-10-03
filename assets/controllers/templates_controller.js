import {Controller} from "@hotwired/stimulus";

const STORAGE_KEY = "payment_order_templates";

export default class extends Controller {

    static targets = ['menu', 'delete_menu'];



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
    }

    deserializeForm(data)
    {
        alert(data);
    }

    _saveTemplatesToStorage()
    {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this._templates));
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

                item.addEventListener('click', () => {
                    this.deserializeForm(template.data);
                });

                menu.appendChild(item);
            }
        });
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