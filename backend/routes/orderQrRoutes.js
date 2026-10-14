'use strict';

const express = require('express');
const { renderOrderQrPage } = require('../controllers/orderQrController');

function createOrderQrRoutes(adminAuth) {
    const router = express.Router();
    router.get('/', adminAuth, renderOrderQrPage);
    return router;
}

module.exports = createOrderQrRoutes;
