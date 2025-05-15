const express = require('express');
const cors = require('cors');
const axios = require('axios');

const app = express();
const port = process.env.PORT || 10000;

app.use(cors());
app.use(express.json());

const PAYHERO_AUTH_TOKEN = process.env.PAYHERO_AUTH_TOKEN || 'Basic ZGFpbHlqb2JzOjM4MTc4MDM3I0lk';
const PAYHERO_API_URL = process.env.PAYHERO_API_URL || 'https://backend.payhero.co.ke/api/v2/payments';

app.post('/api/initiate-payment', async (req, res) => {
    const { phone_number, amount, channel_id, provider, network_code, external_reference, customer_name, callback_url, coverLetterData } = req.body;

    const payload = {
        phone_number,
        amount: parseFloat(amount),
        channel_id: parseInt(channel_id),
        provider,
        network_code,
        external_reference,
        customer_name,
        callback_url
    };

    console.log('Initiating payment with payload:', payload);
    console.log('Using PAYHERO_API_URL:', PAYHERO_API_URL);

    try {
        const response = await axios.post(PAYHERO_API_URL, payload, {
            headers: {
                'Authorization': PAYHERO_AUTH_TOKEN,
                'Content-Type': 'application/json'
            }
        });

        console.log('Pay Hero response:', response.data);
        res.json({ success: true, reference: response.data.reference || external_reference });
    } catch (error) {
        console.error('Error in /api/initiate-payment:', {
            status: error.response ? error.response.status : 'N/A',
            data: error.response ? error.response.data : error.message,
            headers: error.response ? error.response.headers : 'N/A',
            apiUrl: PAYHERO_API_URL
        });
        res.status(error.response ? error.response.status : 500).json({
            success: false,
            error: error.response ? error.response.data : error.message
        });
    }
});

app.post('/api/payment-callback', (req, res) => {
    console.log('Received callback:', req.body);
    res.status(200).send('Callback received');
});

app.get('/api/transaction-status', async (req, res) => {
    const { reference } = req.query;

    console.log(`Checking status for reference: ${reference}`);
    const statusUrl = `${PAYHERO_API_URL}/status?reference=${reference}`;

    try {
        const response = await axios.get(statusUrl, {
            headers: {
                'Authorization': PAYHERO_AUTH_TOKEN
            }
        });

        console.log('Transaction status response:', response.data);
        res.json(response.data);
    } catch (error) {
        console.error('Error in /api/transaction-status:', error.response ? error.response.data : error.message);
        res.status(error.response ? error.response.status : 500).json({
            success: false,
            error: error.response ? error.response.data : error.message
        });
    }
});

app.listen(port, () => {
    console.log(`Server running on port ${port}`);
});
