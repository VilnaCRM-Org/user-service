import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiTooltip, UiTypography } from '@/components/';
import UiImage from '@/components/UiImage';

import { ServicesHoverCard } from '../../features/landing/components/Possibilities/ServicesHoverCard';

import styles from './styles';
import { UiCardItemProps, CardContentProps } from './types';

function CardContent({
  item,
  isSmallCard,
  imageList,
}: CardContentProps): React.ReactElement {
  return (
    <>
      <UiTypography
        variant={isSmallCard ? 'h6' : 'h5'}
        sx={isSmallCard ? styles.smallTitle : styles.largeTitle}
      >
        <Trans i18nKey={item.title} />
      </UiTypography>
      <UiTypography
        variant={isSmallCard ? 'bodyText16' : 'bodyText18'}
        sx={isSmallCard ? styles.smallText : styles.largeText}
      >
        {isSmallCard ? (
          <Trans i18nKey={item.text}>
            Інтегруйте
            <UiTooltip
              placement="bottom"
              arrow
              sx={styles.hoveredCard}
              title={<ServicesHoverCard imageList={imageList || []} />}
            >
              <UiTypography variant="bodyText16">звичні сервіси</UiTypography>
            </UiTooltip>
            у кілька кліків
          </Trans>
        ) : (
          <Trans i18nKey={item.text} />
        )}
      </UiTypography>
    </>
  );
}
function UiCardItem({ item, imageList }: UiCardItemProps): React.ReactElement {
  const { t } = useTranslation();
  const isSmallCard: boolean = item.type === 'smallCard';
  return (
    <Stack sx={isSmallCard ? styles.smallWrapper : styles.largeWrapper}>
      <UiImage
        src={item.imageSrc}
        alt={t(item.alt)}
        sx={isSmallCard ? styles.smallImage : styles.largeImage}
      />
      <Stack flexDirection="column">
        <CardContent
          item={item}
          isSmallCard={isSmallCard}
          imageList={imageList}
        />
      </Stack>
    </Stack>
  );
}
export default UiCardItem;
