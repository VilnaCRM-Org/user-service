import { Box, Container } from '@mui/material';
import dynamic from 'next/dynamic';
import Head from 'next/head';
import { ComponentType } from 'react';
import { useTranslation } from 'react-i18next';

import UiFooter from '../../../../components/UiFooter';
import AboutUs from '../AboutUs';
import BackgroundImages from '../BackgroundImages';
import ForWhoSection from '../ForWhoSection';
import Header from '../Header';

const DynamicAuthSection: ComponentType = dynamic(() => import('../AuthSection'));
const DynamicWhyUs: ComponentType = dynamic(() => import('../WhyUs'));
const DynamicPossibilities: ComponentType = dynamic(() => import('../Possibilities'));

function Landing(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <>
      <Head>
        <title>{t('VilnaCRM')}</title>
        <meta name={t('description')} content={t('The first Ukrainian open source CRM')} />
        <link rel="apple-touch-icon" href="../../assets/img/about-vilna/touch.png" />
        <meta name={t('description')} content={t('The first Ukrainian open source CRM')} />
        <link rel="apple-touch-icon" href="../../assets/img/about-vilna/touch.png" />
      </Head>
      <Header />
      <Box sx={{ position: 'relative' }}>
        <BackgroundImages />
        <AboutUs />
        <Container maxWidth="xl">
          <DynamicWhyUs />
        </Container>
        <ForWhoSection />
        <Container maxWidth="xl">
          <DynamicPossibilities />
        </Container>
      </Box>
      <DynamicAuthSection />
      <UiFooter />
    </>
  );
}

export default Landing;
