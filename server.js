const express = require('express');
const axios = require('axios');
const app = express();

// Enable CORS for requests from GitHub Pages
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', 'https://thecvarchitect.github.io');
  res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') {
    return res.sendStatus(200);
  }
  next();
});

app.use(express.json());

const paymentStatuses = new Map();

app.post('/api/initiate-payment', async (req, res) => {
  const { phone_number, amount, channel_id, provider, network_code, external_reference, customer_name, callback_url } = req.body;
  console.log('Initiating payment with payload:', { phone_number, amount, external_reference, callback_url });
  try {
    const response = await axios.post('https://backend.payhero.co.ke/api/v2/payments', {
      phone_number, amount, channel_id, provider, network_code, external_reference, customer_name, callback_url
    }, {
      headers: { Authorization: process.env.PAYHERO_AUTH_TOKEN }
    });
    const { success, status, reference, CheckoutRequestID } = response.data;
    if (success && status === 'QUEUED') {
      paymentStatuses.set(reference, { status, details: response.data });
      console.log(`Stored initial status for reference: ${reference}`);
    }
    res.json(response.data);
  } catch (error) {
    console.error('Payment initiation error:', error.message);
    res.status(500).json({ success: false, error: error.message });
  }
});

app.post('/api/payment-callback', (req, res) => {
  console.log('Received callback raw:', req.body);
  const { ExternalReference, Status } = req.body;
  if (ExternalReference && Status) {
    const upperCaseStatus = Status.toUpperCase();
    paymentStatuses.set(ExternalReference, { status: upperCaseStatus, details: req.body });
    console.log(`Updated status for reference ${ExternalReference} to ${upperCaseStatus}`);
  } else {
    console.error('Invalid callback payload:', req.body);
  }
  res.json({ success: true });
});

app.get('/api/transaction-status', (req, res) => {
  const { reference } = req.query;
  console.log(`Checking status for reference: ${reference}`);
  const status = paymentStatuses.get(reference);
  if (status) {
    res.json({ success: true, ...status });
  } else {
    res.status(404).json({ success: false, error: 'Status not found' });
  }
});

const PORT = process.env.PORT || 10000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
