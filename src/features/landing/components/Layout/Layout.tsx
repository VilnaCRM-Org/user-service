import React from 'react';

import Footer from '@/features/landing/components/Footer/Footer/Footer';
import Header from '@/features/landing/components/Header/Header/Header';

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <Header />
      <main>{children}</main>
      <Footer />
    </>
  );
}
