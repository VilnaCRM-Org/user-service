import { Box, Grid, useMediaQuery, Stack, useTheme } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '../ui/UiTypography/UiTypography';

interface CardItemInterface {
  item: {
    id: string;
    imageSrc: string;
    title: string;
    text: string;
    width?: number;
    height?: number;
  };
  type: 'WhyUs' | 'Possibilities';
}

function CardItem({ item, type }: CardItemInterface) {
  const { t } = useTranslation();
  const theme = useTheme();
  const mobile = useMediaQuery(theme.breakpoints.down('sm'));

  return (
    <Box>
      {type === 'Possibilities' && (
        <Stack
          sx={{
            height: '100%',
            padding: {
              md: '34px 30px 25px',
              lg: '2.5rem 1.563rem 2rem 2.5rem',
            },
            borderRadius: '0.75rem',
            border: '1px solid #EAECEE',
            flexDirection: { md: 'row', lg: 'column' },
            alignItems: { md: 'center', lg: 'start' },
            justifyContent: { md: 'space-between', lg: 'flex-start' },
          }}
        >
          <Box>
            <Image
              src={item.imageSrc}
              alt="Card Image"
              width={item.width}
              height={item.height}
            />
          </Box>
          <Stack maxWidth="294px">
            <UITypography
              variant="h6"
              sx={{ pt: { md: '0', lg: '32px', xl: '32px' } }}
            >
              {t(item.title)}
            </UITypography>
            <UITypography variant="bodyText16" sx={{ mt: '10px' }}>
              {t(item.text)}
            </UITypography>
          </Stack>
        </Stack>
      )}

      {type === 'WhyUs' && (
        // eslint-disable-next-line react/jsx-no-useless-fragment
        <>
          {!mobile ? (
            <Grid
              key={item.id}
              sx={{
                height: '100%',
                p: '1.5rem',
                borderRadius: '0.75rem',
                border: '1px solid #EAECEE',
              }}
            >
              <Image
                src={item.imageSrc}
                alt="Card Image"
                width={70}
                height={70}
              />
              <UITypography variant="h5" sx={{ pt: '12px' }}>
                {t(item.title)}
              </UITypography>
              <UITypography variant="bodyText18" sx={{ mt: '10px' }}>
                {t(item.text)}
              </UITypography>
            </Grid>
          ) : (
            <Box
              sx={{
                height: '297px',
                mt: '24px',
              }}
            >
              <Grid
                key={item.id}
                sx={{
                  p: '16px 18px 72px 16px',
                  borderRadius: '0.75rem',
                  border: '1px solid #EAECEE',
                  maxHeight: '263px',
                }}
              >
                <Image
                  src={item.imageSrc}
                  alt="Card Image"
                  width={50}
                  height={50}
                />
                <UITypography variant="demi18" sx={{ pt: '16px', pb: '12px' }}>
                  {t(item.title)}
                </UITypography>
                <UITypography variant="bodyMobile">{t(item.text)}</UITypography>
              </Grid>
            </Box>
          )}
        </>
      )}
    </Box>
  );
}

export default CardItem;
