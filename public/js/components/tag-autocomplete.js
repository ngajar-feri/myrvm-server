/**
 * TagAutocomplete - Email-style tag input component
 * Provides autocomplete suggestions and tag/chip selection UI
 */
class TagAutocomplete {
    constructor(inputId, suggestionsId, selectedTagsId) {
        this.input = document.getElementById(inputId);
        this.suggestionsContainer = document.getElementById(suggestionsId);
        this.tagsContainer = document.getElementById(selectedTagsId);
        this.selectedItems = new Map(); // Map of id => item object
        this.allItems = [];
        this.debounceTimer = null;

        if (this.input) {
            this.setupEventListeners();
        }
    }

    setItems(items) {
        this.allItems = items;
    }

    setupEventListeners() {
        // Input event with debounce
        this.input.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                const query = e.target.value.toLowerCase().trim();
                if (query.length < 2) {
                    this.hideSuggestions();
                    return;
                }
                this.showSuggestions(query);
            }, 200);
        });

        // Focus event
        this.input.addEventListener('focus', () => {
            const query = this.input.value.toLowerCase().trim();
            if (query.length >= 2) {
                this.showSuggestions(query);
            }
        });

        // Hide on blur (with delay for click)
        this.input.addEventListener('blur', () => {
            setTimeout(() => this.hideSuggestions(), 200);
        });

        // Keyboard navigation
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideSuggestions();
            }
        });
    }

    showSuggestions(query) {
        const filtered = this.allItems.filter(item =>
            !this.selectedItems.has(String(item.id)) &&
            (item.name.toLowerCase().includes(query) ||
                (item.email && item.email.toLowerCase().includes(query)) ||
                (item.serial_number && item.serial_number.toLowerCase().includes(query)))
        );

        if (filtered.length === 0) {
            this.suggestionsContainer.innerHTML = `
                <div class="autocomplete-empty">
                    <i class="ti ti-search-off"></i> No matches found
                </div>
            `;
            this.suggestionsContainer.style.display = 'block';
            return;
        }

        this.suggestionsContainer.innerHTML = filtered.slice(0, 10).map(item => `
            <div class="autocomplete-item" data-id="${item.id}" data-name="${this.escapeHtml(item.name)}">
                <div class="autocomplete-item-avatar">
                    <i class="ti ${item.email ? 'ti-user' : 'ti-device-desktop'}"></i>
                </div>
                <div class="autocomplete-item-content">
                    <span class="autocomplete-item-name">${this.escapeHtml(item.name)}</span>
                    ${item.email ? `<small class="autocomplete-item-email">${this.escapeHtml(item.email)}</small>` : ''}
                    ${item.serial_number ? `<small class="autocomplete-item-serial">${this.escapeHtml(item.serial_number)}</small>` : ''}
                </div>
            </div>
        `).join('');

        // Add click handlers
        this.suggestionsContainer.querySelectorAll('.autocomplete-item').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const item = this.allItems.find(i => String(i.id) === el.dataset.id);
                if (item) {
                    this.selectItem(item);
                }
            });
        });

        this.suggestionsContainer.style.display = 'block';
    }

    hideSuggestions() {
        this.suggestionsContainer.style.display = 'none';
    }

    selectItem(item) {
        if (this.selectedItems.has(String(item.id))) {
            return; // Already selected
        }

        this.selectedItems.set(String(item.id), item);
        this.addTag(item);
        this.input.value = '';
        this.hideSuggestions();
    }

    addTag(item) {
        const tag = document.createElement('span');
        tag.className = 'tag-chip';
        tag.dataset.id = item.id;
        tag.innerHTML = `
            <span class="tag-chip-text">${this.escapeHtml(item.name)}</span>
            <i class="ti ti-x tag-remove" data-id="${item.id}"></i>
        `;

        tag.querySelector('.tag-remove').addEventListener('click', (e) => {
            e.stopPropagation();
            this.removeTag(item.id);
        });

        this.tagsContainer.appendChild(tag);
    }

    removeTag(id) {
        this.selectedItems.delete(String(id));
        const tag = this.tagsContainer.querySelector(`[data-id="${id}"]`);
        if (tag) {
            tag.remove();
        }
    }

    getSelectedIds() {
        return Array.from(this.selectedItems.keys()).map(id => parseInt(id));
    }

    getSelectedItems() {
        return Array.from(this.selectedItems.values());
    }

    clear() {
        this.selectedItems.clear();
        this.tagsContainer.innerHTML = '';
        this.input.value = '';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Pre-select an item by ID
    preselectById(id) {
        const item = this.allItems.find(i => String(i.id) === String(id));
        if (item && !this.selectedItems.has(String(id))) {
            this.selectItem(item);
        }
    }
}

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.TagAutocomplete = TagAutocomplete;
}
