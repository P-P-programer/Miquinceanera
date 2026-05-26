import React from 'react';
import ReactDOM from 'react-dom/client';
import EventApp from './components/EventApp';

const rootElement = document.getElementById('app');

if (rootElement) {
	ReactDOM.createRoot(rootElement).render(React.createElement(React.StrictMode, null, React.createElement(EventApp)));
}
