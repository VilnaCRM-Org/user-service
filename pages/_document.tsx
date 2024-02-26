import { Html, Head, NextScript } from 'next/document';
import React from 'react';
import { useTranslation } from 'react-i18next';

export default function Document(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Html lang="en">
      <Head>
        <title>{t('VilnaCRM')}</title>
        <meta
          name={t('description') as string}
          content={t('The first Ukrainian open source CRM') as string}
        />
      </Head>
      <body>
        <NextScript />
      </body>
    </Html>
  );
}
