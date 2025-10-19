import React, { useState, useEffect } from 'react';
import { apiService } from './services/apiService';
import { User, Event, Category } from './types';
import Login from './components/Login';
import Register from './components/Register';

// Placeholder componenti (da completare)
const Dashboard = ({ events, categories, user }: any) => (
  <div className="p-4">
    <h2 className="text-2xl font-bold mb-4">Dashboard</h2>
    <p>Benvenuto {user?.email}!</p>
    <p>Eventi totali: {events.length}</p>
    <div className="mt-4 space-y-2">
      {events.slice(0, 5).map((event: Event) => (
        <div key={event.id} className="bg-surface p-3 rounded-md">
          <p className="font-semibold">{event.title}</p>
          <p className="text-sm text-text-secondary">{new Date(event.start_datetime).toLocaleString('it-IT')}</p>
        </div>
      ))}
    </div>
  </div>
);

const App: React.FC = () => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [showRegister, setShowRegister] = useState(false);
  const [user, setUser] = useState<User | null>(null);
  const [events, setEvents] = useState<Event[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    const token = localStorage.getItem('auth_token');
    
    if (!token) {
      setLoading(false);
      return;
    }

    try {
      const userData = await apiService.getCurrentUser();
      setUser(userData);
      setIsAuthenticated(true);
      await loadData();
    } catch (error) {
      console.error('Auth check failed:', error);
      apiService.clearToken();
    } finally {
      setLoading(false);
    }
  };

  const loadData = async () => {
    try {
      const [eventsData, categoriesData] = await Promise.all([
        apiService.getEvents(),
        apiService.getCategories(),
      ]);

      setEvents(eventsData.events || []);
      setCategories(categoriesData || []);
    } catch (error) {
      console.error('Failed to load data:', error);
    }
  };

  const handleLoginSuccess = async () => {
    await checkAuth();
  };

  const handleLogout = () => {
    apiService.logout();
    setIsAuthenticated(false);
    setUser(null);
    setEvents([]);
    setCategories([]);
  };

  if (loading) {
    return (
      <div className="h-screen w-screen bg-background flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-text-secondary">Caricamento...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return showRegister ? (
      <Register
        onSuccess={handleLoginSuccess}
        onSwitchToLogin={() => setShowRegister(false)}
      />
    ) : (
      <Login
        onSuccess={handleLoginSuccess}
        onSwitchToRegister={() => setShowRegister(true)}
      />
    );
  }

  return (
    <div className="h-screen w-screen bg-background text-text-primary flex flex-col font-sans">
      {/* Header */}
      <header className="bg-surface border-b border-gray-700 p-4 flex justify-between items-center">
        <h1 className="text-xl font-bold text-primary">SmartLife AI</h1>
        <div className="flex items-center gap-4">
          <span className="text-sm text-text-secondary">{user?.email}</span>
          <span className="text-xs bg-primary px-2 py-1 rounded-full uppercase">
            {user?.plan}
          </span>
          <button
            onClick={handleLogout}
            className="text-sm text-text-secondary hover:text-white transition-colors"
          >
            Logout
          </button>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-grow overflow-y-auto">
        <Dashboard events={events} categories={categories} user={user} />
      </main>
    </div>
  );
};

export default App;
