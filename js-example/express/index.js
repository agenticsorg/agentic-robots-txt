import express from 'express';
import cors from 'cors';
import jwt from 'jsonwebtoken';
import rateLimit from 'express-rate-limit';
import { WebSocketServer } from 'ws';
import { promises as fs } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

// ES modules compatibility
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Environment variables
const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key';
const NODE_ENV = process.env.NODE_ENV || 'development';
const PORT = process.env.PORT || 3000;

// Initialize Express app
const app = express();

// Middleware
app.use(express.json());
app.use(cors());

// Rate limiting
const limiter = rateLimit({
  windowMs: 60 * 60 * 1000, // 1 hour
  max: 1000, // limit each IP to 1000 requests per windowMs
  standardHeaders: true,
  legacyHeaders: false
});

app.use(limiter);

// JWT verification middleware
const verifyToken = (req, res, next) => {
  const token = req.headers.authorization?.split(' ')[1];
  if (!token) {
    return res.status(401).json({ error: 'No token provided' });
  }
  
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    req.user = decoded;
    next();
  } catch (err) {
    res.status(401).json({ error: 'Invalid token' });
  }
};

// Serve static files from public directory
app.use(express.static(join(__dirname, 'public')));

// Default route - serve test.html
app.get('/', (req, res) => {
  res.sendFile(join(__dirname, 'public/test.html'));
});

// Serve robots.txt
app.get('/robots.txt', async (req, res) => {
  try {
    const content = await fs.readFile(join(__dirname, 'public/robots.txt'), 'utf8');
    res.type('text/plain').send(content);
  } catch (err) {
    res.status(404).send('Not found');
  }
});

// Custom middleware for .well-known files
app.use('/.well-known', async (req, res, next) => {
  const filePath = join(__dirname, 'public/.well-known', req.path);
  
  try {
    const content = await fs.readFile(filePath, 'utf8');
    
    // Set appropriate content type based on file extension
    if (filePath.endsWith('.json')) {
      res.type('application/json');
      // Validate JSON before sending
      try {
        JSON.parse(content);
        res.send(content);
      } catch (e) {
        res.status(500).json({ error: 'Invalid JSON in specification file' });
      }
    } else if (filePath.endsWith('.md')) {
      res.type('text/markdown').send(content);
    } else {
      res.type('text/plain').send(content);
    }
  } catch (err) {
    if (err.code === 'ENOENT') {
      res.status(404).json({ error: 'File not found' });
    } else {
      next(err);
    }
  }
});

// Authentication endpoint
app.post('/auth', async (req, res) => {
  const { client_id, client_secret } = req.body;
  
  // In production, validate credentials against a database
  if (client_id === 'test' && client_secret === 'test') {
    const token = jwt.sign(
      { 
        id: client_id,
        role: 'agent',
        capabilities: ['read', 'execute']
      },
      JWT_SECRET,
      { expiresIn: '1h' }
    );
    
    res.json({ token });
  } else {
    res.status(401).json({ error: 'Invalid credentials' });
  }
});

// Neural Interface endpoint
app.post('/api/brain/inference', verifyToken, async (req, res) => {
  const { model, input } = req.body;
  
  // Mock response based on model
  const responses = {
    'gpt-4o-mini': {
      output: "I'm a simulated GPT-4o-mini response. " + input,
      model_info: {
        name: "gpt-4o-mini",
        version: "1.0.0",
        latency: "150ms"
      }
    },
    'llama-3': {
      output: "I'm a simulated Llama-3 response. " + input,
      model_info: {
        name: "llama-3",
        version: "3.0.0",
        latency: "200ms"
      }
    },
    'claude-3': {
      output: "I'm a simulated Claude-3 response. " + input,
      model_info: {
        name: "claude-3",
        version: "3.0.0",
        latency: "300ms"
      }
    }
  };

  if (!model || !input) {
    return res.status(400).json({
      error: 'Missing required parameters',
      required: ['model', 'input']
    });
  }

  if (!responses[model]) {
    return res.status(400).json({
      error: 'Invalid model',
      available_models: Object.keys(responses)
    });
  }

  // Simulate processing delay
  await new Promise(resolve => setTimeout(resolve, 500));

  res.json({
    success: true,
    timestamp: new Date().toISOString(),
    request_id: Math.random().toString(36).substring(7),
    ...responses[model]
  });
});

// Protected API endpoints
app.get('/api/health', verifyToken, async (req, res) => {
  try {
    const healthData = await fs.readFile(
      join(__dirname, 'public/.well-known/health.json'),
      'utf8'
    );
    res.json(JSON.parse(healthData));
  } catch (err) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create HTTP server
const server = app.listen(PORT, () => {
  const testUrl = `http://localhost:${PORT}`;
  console.log('\n🚀 Server running on port', PORT);
  console.log('\n📝 Interface available at:');
  console.log('\x1b[36m%s\x1b[0m', testUrl);
  console.log('\n🔍 Click the URL above to open in browser\n');
});

// WebSocket server
const wss = new WebSocketServer({ 
  server,
  verifyClient: ({ req, _origin, _secure }, callback) => {
    // Get token from protocols array
    const token = req.headers['sec-websocket-protocol'];
    if (!token) {
      callback(false, 401, 'Unauthorized');
      return;
    }

    try {
      // Verify JWT token
      const decoded = jwt.verify(token, JWT_SECRET);
      req.user = decoded; // Store user info for later use
      callback(true);
    } catch (err) {
      callback(false, 401, 'Invalid token');
    }
  }
});

// Store authenticated WebSocket connections
const clients = new Map();

// WebSocket connection handler
wss.on('connection', async (ws, req) => {
  console.log('New WebSocket connection');
  
  // Store user info from verification
  clients.set(ws, req.user);

  const sendMessage = (type, data = {}) => {
    ws.send(JSON.stringify({
      type,
      timestamp: new Date().toISOString(),
      ...data
    }));
  };

  // Send welcome message
  sendMessage('welcome', {
    message: 'Connected to Agentics WebSocket server',
    user: req.user
  });

  // Handle messages
  ws.on('message', async (message) => {
    try {
      const data = JSON.parse(message.toString());

      switch (data.type) {
        case 'subscribe':
          if (!data.channel) {
            sendMessage('error', { message: 'Channel name is required' });
            return;
          }
          sendMessage('subscribed', {
            channel: data.channel,
            message: `Subscribed to ${data.channel}`
          });
          
          // Simulate channel activity
          setTimeout(() => {
            sendMessage('channel_update', {
              channel: data.channel,
              data: {
                event: 'test_event',
                details: 'This is a test channel update'
              }
            });
          }, 1000);
          break;

        default:
          sendMessage('error', { message: 'Unknown message type' });
      }
    } catch (err) {
      sendMessage('error', { message: 'Invalid message format' });
    }
  });

  // Handle client disconnection
  ws.on('close', () => {
    clients.delete(ws);
    console.log('Client disconnected');
  });

  // Handle errors
  ws.on('error', (error) => {
    console.error('WebSocket error:', error);
    clients.delete(ws);
  });
});

// Error handling middleware
app.use((err, req, res, _next) => {
  console.error(err.stack);
  res.status(500).json({
    error: 'Internal server error',
    message: NODE_ENV === 'development' ? err.message : undefined
  });
});

// Export for testing
export {
  app,
  server,
  wss
};