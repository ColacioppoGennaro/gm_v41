import React, { useState } from 'react';
import { apiService } from '../services/apiService';

interface RegisterProps {
  onSuccess: () => void;
  onSwitchToLogin: () => void;
}

const Register: React.FC<RegisterProps> = ({ onSuccess, onSwitchToLogin }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (password !== passwordConfirm) {
      setError('Le password non corrispondono');
      return;
    }

    if (password.length < 8) {
      setError('La password deve essere lunga almeno 8 caratteri');
      return;
    }

    setLoading(true);

    try {
      await apiService.register(email, password, passwordConfirm);
      onSuccess();
    } catch (err: any) {
      setError(err.message || 'Errore durante la registrazione');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4">
      <div className="bg-surface rounded-lg p-8 w-full max-w-md shadow-2xl">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-primary mb-2">SmartLife</h1>
          <p className="text-text-secondary">Crea il tuo account</p>
        </div>

        {error && (
          <div className="bg-red-500/10 border border-red-500 rounded-md p-3 mb-4">
            <p className="text-red-500 text-sm">{error}</p>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full bg-background border border-gray-600 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="tua@email.com"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Password</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full bg-background border border-gray-600 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Almeno 8 caratteri"
              required
              minLength={8}
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Conferma Password</label>
            <input
              type="password"
              value={passwordConfirm}
              onChange={(e) => setPasswordConfirm(e.target.value)}
              className="w-full bg-background border border-gray-600 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="Ripeti la password"
              required
              minLength={8}
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-primary hover:bg-violet-700 text-white font-semibold py-3 rounded-md transition-colors disabled:opacity-50"
          >
            {loading ? 'Registrazione...' : 'Registrati'}
          </button>
        </form>

        <div className="mt-6 text-center">
          <p className="text-text-secondary text-sm">
            Hai già un account?{' '}
            <button
              onClick={onSwitchToLogin}
              className="text-primary hover:underline font-medium"
            >
              Accedi
            </button>
          </p>
        </div>
      </div>
    </div>
  );
};

export default Register;
