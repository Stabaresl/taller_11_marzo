const verificarGateway = (req, res, next) => {
    const claveRecibida = req.headers['x-internal-key'];
    const claveEsperada = process.env.INTERNAL_KEY;

    // Si el servidor no tiene configurada la clave, es un error de configuración
    if (!claveEsperada) {
        console.error('INTERNAL_KEY no está configurada en las variables de entorno');
        return res.status(500).json({ error: 'Configuración interna inválida' });
    }

    if (!claveRecibida || claveRecibida !== claveEsperada) {
        return res.status(403).json({
            error: 'Acceso no autorizado - solo el Gateway puede acceder'
        });
    }

    next();
};

module.exports = verificarGateway;