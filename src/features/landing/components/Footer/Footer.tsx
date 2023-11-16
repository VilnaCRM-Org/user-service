import React from 'react';

export function Footer({ children }: { children: React.ReactNode }) {
  return (
    <footer>
      <h3>Footer</h3>
      {children}
    </footer>
  );
}
