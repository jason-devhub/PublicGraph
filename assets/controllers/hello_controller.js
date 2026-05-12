import { Controller } from '@hotwired/stimulus';

/**
 * Démonstration minimale : mise à jour du libellé au clic (validation stack Stimulus).
 */
export default class extends Controller {
    static targets = ['label'];

    toggle() {
        this.labelTarget.textContent =
            'Réponse au clic : Stimulus et AssetMapper sont opérationnels.';
    }
}
