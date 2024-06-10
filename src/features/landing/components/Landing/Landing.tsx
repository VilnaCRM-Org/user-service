import { Box, Container } from '@mui/material';
import dynamic from 'next/dynamic';
import Head from 'next/head';
import { ComponentClass } from 'react';
import { useTranslation } from 'react-i18next';

// @ts-expect-error asdf  af sf asfa
const DynamicBackgroundImages: ComponentClass = dynamic(() => import('../BackgroundImages'), {
  ssr: false,
});
// @ts-expect-error asdf  af sf asfa
const DynamicAboutUs: ComponentClass = dynamic(() => import('../AboutUs'), { ssr: false });
// @ts-expect-error asdf  af sf asfa
const DynamicUiFooter: ComponentClass = dynamic(() => import('../../../../components/UiFooter'), {
  ssr: false,
});
// @ts-expect-error asdf  af sf asfa
const DynamicForWhoSection: ComponentClass = dynamic(() => import('../ForWhoSection'), {
  ssr: false,
});
// @ts-expect-error asdf  af sf asfa
const DynamicHeader: ComponentClass = dynamic(() => import('../Header'), { ssr: false });
// @ts-expect-error asdf  af sf asfa
const DynamicPossibilities: ComponentClass = dynamic(() => import('../Possibilities'), {
  ssr: false,
});
// @ts-expect-error asdf  af sf asfa
const DynamicWhyUs: ComponentClass = dynamic(() => import('../WhyUs'), { ssr: false });
// @ts-expect-error asdf  af sf asfa
const DynamicAuthSection: ComponentClass = dynamic(() => import('../AuthSection'), { ssr: false });

function Landing(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <>
      <Head>
        <title>{t('VilnaCRM')}</title>
        <meta name={t('description')} content={t('The first Ukrainian open source CRM')} />
        <link rel="apple-touch-icon" href="../../assets/img/about-vilna/touch.png" />
      </Head>
      <DynamicHeader />
      <Box sx={{ position: 'relative' }}>
        <DynamicBackgroundImages />
        <DynamicAboutUs />
        <Container maxWidth="xl">
          <DynamicWhyUs />
        </Container>
        <DynamicForWhoSection />
        <Container maxWidth="xl">
          <DynamicPossibilities />
        </Container>
      </Box>
      <DynamicAuthSection />
      <DynamicUiFooter />
    </>
  );
}

export default Landing;
