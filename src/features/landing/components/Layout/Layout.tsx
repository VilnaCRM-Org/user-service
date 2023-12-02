import React from 'react';

import Footer from '../Footer/Footer/Footer';
import Header from '../Header/Header/Header';

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <Header />
      <main>{children}</main>
      <Footer />
    </>
  );
}
