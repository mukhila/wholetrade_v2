import qz from 'qz-tray';

/* Certificate: fetched from Laravel (auto-generated on first request) */
qz.security.setCertificatePromise(function (resolve, reject) {
    fetch('/api/qz-cert', { cache: 'no-store' })
        .then(function (r) { return r.ok ? r.text() : Promise.reject('cert HTTP ' + r.status); })
        .then(resolve)
        .catch(reject);
});

/* Signature: server signs the QZ challenge with the stored private key */
qz.security.setSignatureAlgorithm('SHA512');
qz.security.setSignaturePromise(function (toSign) {
    return function (resolve, reject) {
        var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        fetch('/api/qz-sign', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ request: toSign }),
        })
            .then(function (r) { return r.ok ? r.text() : Promise.reject('sign HTTP ' + r.status); })
            .then(resolve)
            .catch(reject);
    };
});

window.QZ = qz;
