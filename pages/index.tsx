import React from 'react';
import { useTranslation } from 'react-i18next';

export default function Home() {
  const { t } = useTranslation();

  return (
    <h1>
      {/* eslint-disable-next-line react/button-has-type */}
      { t('coming-soon') }
    </h1>
  );
}
