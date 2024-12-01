
function registerBatchActionWithoutModal() {
    document.querySelectorAll('[data-action-batch][data-action-batch-no-confirm]').forEach((dataActionBatch) => {

        //Remove the data-bs-toggle attribute to prevent the modal from showing
        dataActionBatch.removeAttribute('data-bs-toggle');

        dataActionBatch.addEventListener('click', (event) => {
            //The internal form for the batch action still got the correct data, so we just need to submit it
            document.querySelector('#modal-batch-action-button').click();
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    registerBatchActionWithoutModal();
});

