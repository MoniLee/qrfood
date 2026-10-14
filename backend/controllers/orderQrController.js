'use strict';

const os = require('node:os');
const QRCode = require('qrcode');

function isPrivateIPv4(address) {
    const octets = address.split('.').map(Number);
    if (octets.length !== 4 || octets.some((octet) => !Number.isInteger(octet) || octet < 0 || octet > 255)) {
        return false;
    }
    return octets[0] === 10 ||
        (octets[0] === 172 && octets[1] >= 16 && octets[1] <= 31) ||
        (octets[0] === 192 && octets[1] === 168);
}

function getLanAddress() {
    const candidates = Object.values(os.networkInterfaces())
        .flatMap((interfaces) => interfaces || [])
        .filter((address) => address.family === 'IPv4' && !address.internal && isPrivateIPv4(address.address))
        .map((address) => address.address);

    return candidates.sort((left, right) => {
        const priority = (address) => address.startsWith('192.168.') ? 0 : address.startsWith('10.') ? 1 : 2;
        return priority(left) - priority(right);
    })[0] || null;
}

function getPublicOrigin() {
    if (process.env.QR_PUBLIC_URL) {
        const configured = new URL(process.env.QR_PUBLIC_URL);
        if (!['http:', 'https:'].includes(configured.protocol)) {
            throw new Error('QR_PUBLIC_URL must use HTTP or HTTPS.');
        }
        return configured.origin;
    }

    const address = getLanAddress();
    if (!address) {
        throw new Error('No private LAN IPv4 address found. Set QR_PUBLIC_URL to the address customers can access.');
    }

    return `http://${address}:${Number(process.env.PORT || 3000)}`;
}

async function renderOrderQrPage(_req, res, next) {
    try {
        const origin = getPublicOrigin();
        const url = `${origin}${res.locals.baseUrl || ''}/menu`;
        const qrImage = await QRCode.toDataURL(url, { errorCorrectionLevel: 'M', margin: 2, width: 320 });
        res.render('pages/order-qr', {
            ...res.locals,
            title: 'Guest ordering QR | Velvet Plate',
            orderUrl: url,
            qrImage,
        });
    } catch (error) {
        next(error);
    }
}

module.exports = { renderOrderQrPage };
