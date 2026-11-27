import './bootstrap';
import './admin';

const cards = [...document.querySelectorAll('.thesis-card')];
const searchInput = document.querySelector('#catalog-search');
const departmentFilter = document.querySelector('#department-filter');
const yearFilter = document.querySelector('#year-filter');
const sortSelect = document.querySelector('#sort-select');
const resultsCount = document.querySelector('#results-count');
const emptyState = document.querySelector('#empty-state');
const catalogGrid = document.querySelector('#catalog-grid');
const toast = document.querySelector('#toast');
let toastTimeout;

function filterCatalog() {
    if (!searchInput || !departmentFilter || !yearFilter || !sortSelect || !resultsCount || !emptyState || !catalogGrid) return;

    const query = searchInput.value.trim().toLocaleLowerCase('fr');
    const department = departmentFilter.value;
    const year = yearFilter.value;
    const visibleCards = cards.filter((card) => {
        const matchesQuery = !query || card.dataset.search.toLocaleLowerCase('fr').includes(query);
        const matchesDepartment = !department || card.dataset.department === department;
        const matchesYear = !year || card.dataset.year === year;
        card.hidden = !(matchesQuery && matchesDepartment && matchesYear);
        return !card.hidden;
    });

    if (sortSelect.value === 'title') {
        visibleCards.sort((first, second) => first.dataset.title.localeCompare(second.dataset.title, 'fr'));
    } else if (sortSelect.value === 'oldest') {
        visibleCards.sort((first, second) => Number(first.dataset.year) - Number(second.dataset.year));
    } else {
        visibleCards.sort((first, second) => Number(second.dataset.year) - Number(first.dataset.year));
    }

    visibleCards.forEach((card) => catalogGrid.insertBefore(card, emptyState));
    resultsCount.textContent = `${visibleCards.length} mémoire${visibleCards.length === 1 ? '' : 's'} affiché${visibleCards.length === 1 ? '' : 's'}`;
    emptyState.hidden = visibleCards.length > 0;
}

function showToast(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('is-visible');
    window.clearTimeout(toastTimeout);
    toastTimeout = window.setTimeout(() => toast.classList.remove('is-visible'), 4200);
}

document.querySelector('#search-button')?.addEventListener('click', filterCatalog);
searchInput?.addEventListener('input', filterCatalog);
departmentFilter?.addEventListener('change', filterCatalog);
yearFilter?.addEventListener('change', filterCatalog);
sortSelect?.addEventListener('change', filterCatalog);

document.querySelector('#reset-filters')?.addEventListener('click', () => {
    searchInput.value = '';
    departmentFilter.value = '';
    yearFilter.value = '';
    sortSelect.value = 'newest';
    filterCatalog();
});

document.querySelector('#mobile-toggle')?.addEventListener('click', (event) => {
    const button = event.currentTarget;
    const navigation = document.querySelector('#main-nav');
    const isOpen = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!isOpen));
    navigation.classList.toggle('is-open', !isOpen);
});

document.querySelectorAll('#main-nav a').forEach((link) => {
    link.addEventListener('click', () => {
        document.querySelector('#main-nav').classList.remove('is-open');
        document.querySelector('#mobile-toggle').setAttribute('aria-expanded', 'false');
    });
});

document.querySelectorAll('dialog.modal').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.querySelector('.modal-close')?.addEventListener('click', () => dialog.close());
});

document.querySelectorAll('[data-open-dialog]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.querySelector(button.dataset.openDialog);
        document.querySelectorAll('dialog[open]').forEach((dialog) => {
            if (dialog !== target) dialog.close();
        });
        target?.showModal();
        document.querySelector('#main-nav')?.classList.remove('is-open');
        document.querySelector('#mobile-toggle')?.setAttribute('aria-expanded', 'false');
    });
});

let activeThesis = null;
document.querySelectorAll('[data-thesis]').forEach((button) => {
    button.addEventListener('click', () => {
        activeThesis = button.closest('.thesis-card');
        document.querySelector('#detail-title').textContent = activeThesis.dataset.title;
        document.querySelector('#detail-author').textContent = activeThesis.dataset.author;
        document.querySelector('#detail-department').textContent = activeThesis.dataset.departmentLabel;
        document.querySelector('#detail-year').textContent = activeThesis.dataset.year;
        document.querySelector('#detail-abstract').textContent = activeThesis.dataset.abstract;
        const isRealThesis = activeThesis.dataset.real === 'true';
        const canViewDocument = isRealThesis && activeThesis.dataset.canView === 'true';
        const canStartPayment = isRealThesis && activeThesis.dataset.canPay === 'true';
        const paymentUnconfigured = isRealThesis && activeThesis.dataset.paymentUnconfigured === 'true';
        document.querySelector('#detail-access-note').textContent = isRealThesis
            ? canViewDocument
                ? 'La fiche est publiée. Vous pouvez consulter le PDF dans la visionneuse sécurisée.'
                : canStartPayment
                    ? 'L’accès intégral à ce mémoire coûte 1 000 F CFA. Le paiement sera confirmé côté serveur.'
                    : paymentUnconfigured
                        ? 'L’accès intégral externe nécessite un paiement sécurisé.'
                        : 'Connectez-vous pour accéder au mémoire selon votre profil.'
            : 'L’aperçu public est limité. L’accès intégral dépend de votre statut étudiant ou d’un paiement confirmé.';
        document.querySelector('#open-preview').hidden = isRealThesis;
        const openDocument = document.querySelector('#open-document');
        openDocument.href = activeThesis.dataset.viewerUrl || '#';
        openDocument.hidden = !canViewDocument;
        document.querySelector('#full-access').hidden = canViewDocument || canStartPayment || paymentUnconfigured;
        document.querySelector('#payment-configuration-note').hidden = !paymentUnconfigured;
        const paymentForm = document.querySelector('#start-payment-form');
        paymentForm.action = activeThesis.dataset.paymentUrl || '#';
        paymentForm.hidden = !canStartPayment;
        document.querySelector('#detail-dialog').showModal();
    });
});

document.querySelector('#open-preview')?.addEventListener('click', () => {
    if (!activeThesis) return;
    document.querySelector('#reader-title').textContent = activeThesis.dataset.title;
    document.querySelector('#reader-cover-title').textContent = activeThesis.dataset.title;
    document.querySelector('#reader-dialog').showModal();
});

document.querySelector('#full-access')?.addEventListener('click', () => {
    if (activeThesis?.dataset.real === 'true' && activeThesis.dataset.authenticated === 'true') {
        showToast('Le paiement de cet accès n’est pas disponible pour ce profil.');
        return;
    }
    document.querySelector('#detail-dialog').close();
    document.querySelector('#auth-dialog').showModal();
    showToast('Connectez-vous pour consulter les mémoires accessibles à votre profil.');
});

const loginForm = document.querySelector('#login-form');
const loginIdentifier = document.querySelector('#login-identifier');
const loginPasswordField = document.querySelector('#login-password-field');
const loginPassword = document.querySelector('#login-password');
const loginSubmit = document.querySelector('#login-submit');
const loginMethodNote = document.querySelector('#login-method-note');
const staffPasswordToggle = document.querySelector('#staff-password-toggle');
const passwordResetLink = document.querySelector('#password-reset-link');
let staffPasswordMode = false;

const updateLoginMethod = () => {
    if (!loginForm || !loginIdentifier || !loginPasswordField || !loginPassword || !loginSubmit) return;

    const usesEmail = loginIdentifier.value.includes('@');
    const usesPassword = usesEmail || staffPasswordMode;
    loginIdentifier.name = usesPassword ? 'identifier' : 'matricule';
    loginPasswordField.hidden = !usesPassword;
    loginPassword.required = usesPassword;
    loginForm.action = usesPassword
        ? loginForm.dataset.passwordAction
        : loginForm.dataset.studentCodeAction;
    loginSubmit.textContent = usesPassword ? 'Se connecter' : 'Recevoir un code';
    if (loginMethodNote) loginMethodNote.hidden = usesPassword;
    if (passwordResetLink) passwordResetLink.hidden = !usesPassword;
    if (staffPasswordToggle) {
        staffPasswordToggle.hidden = usesEmail;
        staffPasswordToggle.textContent = staffPasswordMode
            ? 'Étudiant : recevoir un code par e-mail'
            : 'Personnel ENEAM : connexion par mot de passe';
    }
};

loginIdentifier?.addEventListener('input', () => {
    staffPasswordMode = false;
    updateLoginMethod();
});
staffPasswordToggle?.addEventListener('click', () => {
    staffPasswordMode = !staffPasswordMode;
    updateLoginMethod();
});
document.querySelector('[data-return-to-login]')?.addEventListener('click', (event) => {
    const codeForm = event.currentTarget.closest('form');
    if (codeForm) codeForm.hidden = true;
    if (loginForm) loginForm.hidden = false;
    loginIdentifier?.focus();
});
updateLoginMethod();

document.querySelector('#deposit-form')?.addEventListener('submit', (event) => {
    const file = document.querySelector('#thesis-file').files[0];
    if (!file || (file.type && file.type !== 'application/pdf') || !file.name.toLowerCase().endsWith('.pdf')) {
        event.preventDefault();
        showToast('Veuillez sélectionner un document PDF valide.');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        event.preventDefault();
        showToast('Le fichier dépasse la limite de 5 Mo.');
        return;
    }
    showToast('Envoi du mémoire en cours…');
});

document.querySelector('#thesis-file')?.addEventListener('change', (event) => {
    const file = event.currentTarget.files[0];
    const fileName = document.querySelector('#file-name');
    if (fileName) fileName.textContent = file ? file.name : 'Aucun fichier sélectionné';
});

document.querySelectorAll('.review-action-form').forEach((form) => {
    const analysisStatus = form.querySelector('[name="analysis_status"]');
    const score = form.querySelector('[name="similarity_percent"]');
    const analysisNote = form.querySelector('[name="analysis_note"]');
    const errorNote = form.querySelector('.review-error-note');
    const decision = form.querySelector('[name="decision"]');
    const decisionReason = form.querySelector('[name="reason"]');
    const physicalCompatible = form.querySelector('[name="physical_compatible"]');

    const updateConditionalFields = () => {
        if (analysisStatus) {
            const isError = analysisStatus.value === 'error';
            score.required = !isError;
            score.disabled = isError;
            analysisNote.required = isError;
            analysisNote.disabled = !isError;
            if (errorNote) errorNote.hidden = !isError;
        }

        if (decision) {
            const isPublishing = decision.value === 'publish';
            decisionReason.required = !isPublishing;
            decisionReason.disabled = isPublishing;
            physicalCompatible.required = isPublishing;
            physicalCompatible.disabled = !isPublishing;
        }
    };

    analysisStatus?.addEventListener('change', updateConditionalFields);
    decision?.addEventListener('change', updateConditionalFields);
    updateConditionalFields();
});

filterCatalog();
document.querySelector('dialog[data-auto-open]')?.showModal();
