require('dotenv').config();
const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');

const ventasRoutes = require('./routes/ventas');

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());
app.use(express.json());

app.use('/api/ventas', ventasRoutes);

app.get('/', (req, res) => {
  res.json({ mensaje: 'Microservicio de ventas (MongoDB) funcionando' });
});

mongoose.connect(process.env.MONGO_URI)
  .then(() => {
    console.log('Conectado a MongoDB');
    app.listen(PORT, () => {
      console.log(`Servidor ventas escuchando en http://localhost:${PORT}`);
    });
  })
  .catch(err => {
    console.error('Error conectando a MongoDB', err);
  });
