import React from 'react';
import { Header } from '@/features/landing/components/Header/Header/Header';
import { Footer } from '@/features/landing/components/Footer/Footer';

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <Header />
      <main>{children}</main>
      <Footer>
        <p>Footer text</p>
      </Footer>
    </>
  );
}
