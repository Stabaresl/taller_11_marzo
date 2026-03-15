const verificarGateway = (req, res, next) => {
    const claveRecibida = req.headers['x-internal-key'];
    const claveEsperada = process.env.INTERNAL_KEY;

    if (!claveRecibida || claveRecibida !== claveEsperada) {
        return res.status(403).json({
            error: 'Acceso no autorizado - solo el Gateway puede acceder'
        });
    }
    next();
};

module.exports = verificarGateway;