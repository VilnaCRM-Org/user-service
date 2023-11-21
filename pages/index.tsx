import { GetServerSideProps } from 'next';
import React from 'react';

import { LandingComponent } from '@/features/landing';

export default function Home() {
  return <LandingComponent />;
}

function parseAcceptLanguageHeader(acceptLanguageHeader: string | undefined): string[] {
  if (!acceptLanguageHeader) {
    return [];
  }

  const languageTags = acceptLanguageHeader.split(',');

  const preferredLanguages = languageTags.map((tag) => tag.split(';')[0].trim());

  return preferredLanguages;
}

export const getServerSideProps: GetServerSideProps = async (context) => {
  // Get the request object from the context
  const { req } = context;

  // Access the Accept-Language header from the request
  const acceptLanguageHeader = req.headers['accept-language'];

  // Parse the header to get the preferred languages
  const preferredLanguages = parseAcceptLanguageHeader(acceptLanguageHeader);

  return {
    props: {
      language: preferredLanguages || 'en',
    },
  };
};
