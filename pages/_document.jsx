import { Html, Head, NextScript } from 'next/document';
import React from 'react';

export default function Document() {
  return (
    <Html lang="en">
      <Head>
        <title>VilnaCRM</title>
        <meta
          name="description"
          content="The first Ukrainian open source CRM"
        />
      </Head>
      <body>
        <NextScript />
      </body>
    </Html>
  );
}
