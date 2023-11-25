import React, { useEffect } from 'react';

import Footer from '@/features/landing/components/Footer/Footer/Footer';
import Header from '@/features/landing/components/Header/Header/Header';

import i18n from '../../../../../i18n';

export default function Layout({ children }: { children: React.ReactNode }) {
  useEffect(() => {
    const { language } = navigator;
    i18n.changeLanguage(language);
  }, []);

  return (
    <>
      <Header />
      <main>{children}</main>
      <Footer />
    </>
  );
}
