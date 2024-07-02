import { Box, Container } from '@mui/material';
import Head from 'next/head';
import { useTranslation } from 'react-i18next';

import UiFooter from '@/components/UiFooter';

import Header from '../../../landing/components/Header';
import ApiInfo from '../ApiInfo/ApiInfo';
import Navigation from '../Navigation/Navigation';

import styles from './styles';

function Swagger(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <>
      <Head>
        <title>{t('VilnaCRM API')}</title>
        <meta name={t('description')} content={t('The first Ukrainian open source CRM')} />
        <link rel="apple-touch-icon" href="../../assets/img/about-vilna/touch.png" />
      </Head>
      <Header />
      <Box sx={styles.wrapper}>
        <Container maxWidth="xl">
          <Navigation />
          <ApiInfo />
        </Container>
      </Box>
      <UiFooter />
    </>
  );
}

export default Swagger;
