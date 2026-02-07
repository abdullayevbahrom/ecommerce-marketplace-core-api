/**
 * Didox E-IMZO Integration Example
 * 
 * This file demonstrates how to integrate E-IMZO signatures with your application
 * using the Didox API endpoints.
 */

// API Base URL - adjust according to your setup
const API_BASE_URL = '/api/user';

/**
 * Example: Register user with E-IMZO signature
 */
async function didoxRegister() {
    try {
        // First, get the E-IMZO signature data from the browser plugin
        // This is a simplified example - actual implementation depends on your E-IMZO setup
        
        const signatureData = await getEIMZOSignature(); // Your E-IMZO integration function
        
        const registrationData = {
            taxId: '123456789', // User's tax ID (INN)
            name: 'John Doe',
            email: 'john@example.com',
            phone: '+998901234567', // Optional
            address: 'Tashkent, Uzbekistan', // Optional
            pkcs7: signatureData.pkcs7_64,
            signatureHex: signatureData.signature_hex
        };
        
        const response = await fetch(`${API_BASE_URL}/didox-register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(registrationData)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            console.log('Registration successful:', result.data);
            // Store user token for future requests
            localStorage.setItem('authToken', result.data.user.token);
            return result.data;
        } else {
            console.error('Registration failed:', result.errors);
            throw new Error(result.errors);
        }
        
    } catch (error) {
        console.error('Registration error:', error);
        throw error;
    }
}

/**
 * Example: Login with E-IMZO signature
 */
async function didoxLogin() {
    try {
        // Get the tax ID to sign (should be in base64 format for signing)
        const taxId = '123456789';
        
        // Sign the tax ID with E-IMZO
        const signatureData = await signWithEIMZO(btoa(taxId)); // Your E-IMZO signing function
        
        const loginData = {
            taxId: taxId,
            pkcs7: signatureData.pkcs7_64,
            signatureHex: signatureData.signature_hex
        };
        
        const response = await fetch(`${API_BASE_URL}/didox-login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(loginData)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            console.log('Login successful:', result.data);
            // Store user token for future requests
            localStorage.setItem('authToken', result.data.user.token);
            return result.data;
        } else {
            console.error('Login failed:', result.errors);
            throw new Error(result.errors);
        }
        
    } catch (error) {
        console.error('Login error:', error);
        throw error;
    }
}

/**
 * Example: Attach timestamp to signature
 */
async function attachTimestamp(pkcs7, signatureHex) {
    try {
        const response = await fetch(`${API_BASE_URL}/attach-timestamp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                pkcs7: pkcs7,
                signatureHex: signatureHex
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            return result.data.timeStampTokenB64;
        } else {
            throw new Error(result.errors);
        }
        
    } catch (error) {
        console.error('Timestamp error:', error);
        throw error;
    }
}

/**
 * Example: Get document data for signing
 */
async function getDocumentData(documentId) {
    try {
        const response = await fetch(`${API_BASE_URL}/get-document-data?documentId=${documentId}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (response.ok) {
            return result.data;
        } else {
            throw new Error(result.errors);
        }
        
    } catch (error) {
        console.error('Document data error:', error);
        throw error;
    }
}

/**
 * Example: Sign document with E-IMZO
 */
async function signDocument(documentId) {
    try {
        // First get the document data
        const documentData = await getDocumentData(documentId);
        
        // Convert document to base64 for signing
        const documentBase64 = btoa(JSON.stringify(documentData));
        
        // Sign the document with E-IMZO
        const signatureData = await signWithEIMZO(documentBase64);
        
        const response = await fetch(`${API_BASE_URL}/sign-document`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                documentId: documentId,
                pkcs7: signatureData.pkcs7_64,
                signatureHex: signatureData.signature_hex
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            console.log('Document signed successfully:', result.data);
            return result.data;
        } else {
            throw new Error(result.errors);
        }
        
    } catch (error) {
        console.error('Document signing error:', error);
        throw error;
    }
}

/**
 * Placeholder function for E-IMZO signature generation
 * You need to implement this based on your E-IMZO browser plugin
 */
async function getEIMZOSignature() {
    // This is a placeholder - implement according to your E-IMZO integration
    // Usually involves calling browser plugin methods
    console.log('Implement E-IMZO signature generation here');
    throw new Error('E-IMZO signature generation not implemented');
}

/**
 * Placeholder function for E-IMZO document signing
 * You need to implement this based on your E-IMZO browser plugin
 */
async function signWithEIMZO(dataToSign) {
    // This is a placeholder - implement according to your E-IMZO integration
    // Usually involves calling browser plugin methods to sign the provided data
    console.log('Implement E-IMZO document signing here for data:', dataToSign);
    throw new Error('E-IMZO document signing not implemented');
}

// Example usage:
/*
// Registration
didoxRegister().then(result => {
    console.log('User registered:', result);
}).catch(error => {
    console.error('Registration failed:', error);
});

// Login
didoxLogin().then(result => {
    console.log('User logged in:', result);
}).catch(error => {
    console.error('Login failed:', error);
});

// Sign document
signDocument(123).then(result => {
    console.log('Document signed:', result);
}).catch(error => {
    console.error('Document signing failed:', error);
});
*/ 