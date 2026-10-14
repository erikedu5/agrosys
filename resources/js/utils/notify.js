export function notify(message, type = 'info') {
    window.dispatchEvent(
        new CustomEvent('notify', {
            detail: { message, type },
        })
    );
}

