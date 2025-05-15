const express = require('express');
const axios = require('axios');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const app = express();

app.use(cors());
app.use(express.json());

// Rate limiting: max 100 requests per 15 minutes per IP
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 100,
    message: 'Too many requests, please try again later.'
});
app.use('/api/initiate-payment', limiter);

// In-memory storage for session data (replace with database in production)
const sessions = {};

// Pay Hero API configuration
const PAYHERO_API_URL = process.env.PAYHERO_API_URL || 'https://backend.payhero.co.ke/api/v2';
const PAYHERO_AUTH_TOKEN = process.env.PAYHERO_AUTH_TOKEN || 'Basic eUpqa2gwZ1RRVVdFTzJXRG42a0Q6T3FEelBnRDRwZHFsdkNQaUJHcGVqcEJjNUdPMjJNQWFmSTdDd1EwOQ==';
const CALLBACK_URL = 'https://dailyjobs.co.ke/api/payment-callback';

// Initiate STK Push
app.post('/api/initiate-payment', async (req, res) => {
    try {
        const { phone_number, amount, channel_id, provider, external_reference, customer_name, callback_url } = req.body;
        if (!phone_number || !amount || !channel_id || !provider || !external_reference || !callback_url) {
            return res.status(400).json({ error: 'Missing required fields' });
        }

        // Validate phone number
        if (!phone_number.match(/^07[0-9]{8}$/)) {
            return res.status(400).json({ error: 'Invalid phone number format. Use 0712345678 format.' });
        }

        // Store cover letter data in session
        const session_id = external_reference;
        sessions[session_id] = {
            coverLetterData: req.body.coverLetterData || localStorage.getItem('coverLetterData'),
            status: 'pending',
            checkoutRequestID: null
        };

        const response = await axios.post(`${PAYHERO_API_URL}/payments`, {
            phone_number,
            amount,
            channel_id,
            provider,
            external_reference,
            customer_name,
            callback_url
        }, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': PAYHERO_AUTH_TOKEN
            }
        });

        if (response.data.success) {
            sessions[session_id].checkoutRequestID = response.data.CheckoutRequestID;
        }

        res.json(response.data);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Payment callback
app.post('/api/payment-callback', (req, res) => {
    try {
        const { response, status } = req.body;
        const { CheckoutRequestID, ExternalReference, ResultCode } = response;
        if (status && ResultCode === 0 && ExternalReference && sessions[ExternalReference]) {
            sessions[ExternalReference].status = 'completed';
        }
        res.json({ status: 'received' });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Check transaction status
app.get('/api/transaction-status/:checkoutRequestID', async (req, res) => {
    try {
        const { checkoutRequestID } = req.params;
        const response = await axios.get(`${PAYHERO_API_URL}/transaction/status/${checkoutRequestID}`, {
            headers: {
                'Authorization': PAYHERO_AUTH_TOKEN
            }
        });
        res.json(response.data);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Use Render's dynamic port
const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`Server running on port ${port}`));
