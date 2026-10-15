import { createRoot } from 'react-dom/client';
import PriceSettings from './components/PriceSettings';
import ReportsApp from './components/Reports/ReportsApp';

// import './price-settings.jsx';
import './price-updates.jsx';
import './quotations-blade.jsx';


const priceSettingsRoot = document.getElementById('price-settings-root');
if (priceSettingsRoot) {
    createRoot(priceSettingsRoot).render(<PriceSettings />);
}

const reportsRoot = document.getElementById('reports-root');
if (reportsRoot) {
    createRoot(reportsRoot).render(<ReportsApp />);
}
