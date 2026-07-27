import { Component } from 'react';

export default class ErrorBoundary extends Component {
    state = { hasError: false };

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error, info) {
        console.error('React island crashed:', error, info);
    }

    render() {
        if (this.state.hasError) {
            return (
                <p className="text-sm text-red-600 p-4">
                    Something went wrong loading this section. Please refresh the page.
                </p>
            );
        }
        return this.props.children;
    }
}
