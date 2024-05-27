import { Box, Container } from '@mui/material';
import Head from 'next/head';
import { useTranslation } from 'react-i18next';

import { UiFooter } from '../../../../components/UiFooter';
import { AboutUs } from '../AboutUs';
import { AuthSection } from '../AuthSection';
import { BackgroundImages } from '../BackgroundImages';
import { ForWhoSection } from '../ForWhoSection';
import { Header } from '../Header';
import { Possibilities } from '../Possibilities';
import { WhyUs } from '../WhyUs';

function Landing(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <>
      <Head>
        <title>{t('VilnaCRM')}</title>
        <meta name={t('description')} content={t('The first Ukrainian open source CRM')} />
        <link rel="apple-touch-icon" href="../../assets/img/about-vilna/touch.png" />
      </Head>
      <Header />
      <Box sx={{ position: 'relative' }}>
        <BackgroundImages />
        <AboutUs />
        <Container maxWidth="xl">
          <WhyUs />
        </Container>
        <ForWhoSection />
        <Container maxWidth="xl">
          <Possibilities />
        </Container>
      </Box>
      <AuthSection />
      <UiFooter />
    </>
  );
}

export default Landing;
