const express = require('express');
const axios = require('axios');
const app = express();

// CORS for GitHub Pages
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', 'https://thecvarchitect.github.io');
  res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.sendStatus(200);
  next();
});

app.use(express.json());

// In-memory payment status store
const paymentStatuses = new Map();

// Use your actual PayHero Basic Auth token here
const PAYHERO_AUTH_TOKEN = 'Basic eUpqa2gwZ1RRVVdFTzJXRG42a0Q6T3FEelBnRDRwZHFsdkNQaUJHcGVqcEJjNUdPMjJNQWFmSTdDd1EwOQ==';

// Initiate payment
app.post('/api/initiate-payment', async (req, res) => {
  const {
    phone_number,
    amount,
    channel_id,
    provider,
    network_code,
    external_reference,
    customer_name,
    callback_url
  } = req.body;

  console.log('📤 Initiating payment with payload:', {
    phone_number,
    amount,
    external_reference,
    callback_url
  });

  try {
    const response = await axios.post('https://backend.payhero.co.ke/api/v2/payments', {
      phone_number,
      amount,
      channel_id,
      provider,
      network_code,
      external_reference,
      customer_name,
      callback_url
    }, {
      headers: {
        Authorization: PAYHERO_AUTH_TOKEN,
        'Content-Type': 'application/json'
      }
    });

    const data = response.data;
    console.log('✅ PayHero response:', data);

    if (data.success && data.status === 'QUEUED') {
      paymentStatuses.set(data.reference, { status: data.status, details: data });
      console.log(`🟢 Payment queued. Tracking reference: ${data.reference}`);
    }

    res.json(data);
  } catch (error) {
    console.error('❌ Payment initiation error:', error.message);
    console.error('📩 PayHero error response:', error.response?.data || 'No response body');

    res.status(500).json({
      success: false,
      error: error.response?.data?.message || error.message || 'Unknown error occurred'
    });
  }
});

// Callback from PayHero (payment updates)
app.post('/api/payment-callback', (req, res) => {
  console.log('📥 Payment callback received:', req.body);

  const { ExternalReference, Status } = req.body;
  if (ExternalReference && Status) {
    const statusUpper = Status.toUpperCase();
    paymentStatuses.set(ExternalReference, {
      status: statusUpper,
      details: req.body
    });
    console.log(`🔄 Payment status updated for ${ExternalReference}: ${statusUpper}`);
  } else {
    console.warn('⚠️ Invalid callback payload received:', req.body);
  }

  res.json({ success: true });
});

// Polling: Check payment status
app.get('/api/transaction-status', (req, res) => {
  const { reference } = req.query;
  console.log(`🔍 Checking payment status for reference: ${reference}`);

  const status = paymentStatuses.get(reference);
  if (status) {
    res.json({ success: true, ...status });
  } else {
    res.status(404).json({
      success: false,
      error: 'Status not found or still pending.'
    });
  }
});

// Server start
const PORT = process.env.PORT || 10000;
app.listen(PORT, () => {
  console.log(`🚀 Server is running on port ${PORT}`);
});
