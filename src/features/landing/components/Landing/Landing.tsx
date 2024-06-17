import { Box, Container } from '@mui/material';
import dynamic from 'next/dynamic';
import Head from 'next/head';
import { ComponentType } from 'react';
import { useTranslation } from 'react-i18next';

const DynamicBackgroundImages: ComponentType = dynamic(() => import('../BackgroundImages'));
const DynamicAboutUs: ComponentType = dynamic(() => import('../AboutUs'));
const DynamicUiFooter: ComponentType = dynamic(() => import('../../../../components/UiFooter'));
const DynamicForWhoSection: ComponentType = dynamic(() => import('../ForWhoSection'));
const DynamicHeader: ComponentType = dynamic(() => import('../Header'));
const DynamicPossibilities: ComponentType = dynamic(() => import('../Possibilities'));
const DynamicWhyUs: ComponentType = dynamic(() => import('../WhyUs'));
const DynamicAuthSection: ComponentType = dynamic(() => import('../AuthSection'));

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
