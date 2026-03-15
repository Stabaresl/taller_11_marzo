require('dotenv').config();
const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');

const verificarGateway = require('./middleware/verificarGateway');
const ventasRoutes = require('./routes/ventas');

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());
app.use(express.json());

// --- Health check (no requiere clave interna) ---
app.get('/health', (req, res) => {
  res.json({ status: 'ok', servicio: 'ventas-express' });
});

// --- Rutas protegidas con el middleware del Gateway ---
app.use('/api/ventas', verificarGateway, ventasRoutes);

// --- Manejo de rutas no encontradas ---
app.use((req, res) => {
  res.status(404).json({ error: 'Ruta no encontrada' });
});

// --- Manejo de errores globales ---
app.use((err, req, res, next) => {
  console.error('Error no controlado:', err);
  res.status(500).json({ error: 'Error interno del servidor' });
});

// --- Conexión a MongoDB y arranque del servidor ---
mongoose.connect(process.env.MONGO_URI)
  .then(() => {
    console.log('Conectado a MongoDB');
    app.listen(PORT, () => {
      console.log(`Servidor ventas escuchando en http://localhost:${PORT}`);
    });
  })
  .catch(err => {
    console.error('Error conectando a MongoDB:', err);
    process.exit(1);
  });