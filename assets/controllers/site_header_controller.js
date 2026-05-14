import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'panel'];

    connect() {
        this.close();
    }

    toggle() {
        const isExpanded = this.buttonTarget.getAttribute('aria-expanded') === 'true';
        this.setExpanded(!isExpanded);
    }

    close() {
        this.setExpanded(false);
    }

    setExpanded(isExpanded) {
        this.buttonTarget.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        this.panelTarget.classList.toggle('hidden', !isExpanded);
    }
}
