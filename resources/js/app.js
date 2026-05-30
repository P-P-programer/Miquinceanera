import React from 'react';
import ReactDOM from 'react-dom/client';
import AlbumApp from './components/AlbumApp';
import EventApp from './components/EventApp';

const rootElement = document.getElementById('app');
const activePath = window.location.pathname;
const RootComponent = activePath.startsWith('/album') ? AlbumApp : EventApp;

if (rootElement) {
	ReactDOM.createRoot(rootElement).render(React.createElement(React.StrictMode, null, React.createElement(RootComponent)));
}
