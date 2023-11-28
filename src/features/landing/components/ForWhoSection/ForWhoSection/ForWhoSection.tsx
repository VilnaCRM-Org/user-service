import { Box, Grid } from '@mui/material';
import { useMemo, useState } from 'react';

import { ForWhoImagesContent } from '@/features/landing/components/ForWhoSection/ForWhoImagesContent/ForWhoImagesContent';
import { ForWhoMainTextsContent } from '@/features/landing/components/ForWhoSection/ForWhoMainTextsContent/ForWhoMainTextsContent';
import { ForWhoSectionCardItem } from '@/features/landing/components/ForWhoSection/ForWhoSectionCardItem/ForWhoSectionCardItem';
import ForWhoSectionCards from '@/features/landing/components/ForWhoSection/ForWhoSectionCards/ForWhoSectionCards';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { scrollToRegistrationSection } from '@/features/landing/utils/helpers/scrollToRegistrationSection';

import ForWhoSectionCardsMobile from '../ForWhoSectionCardsMobile/ForWhoSectionCardsMobile';

const images = {
  mainImage: {
    src: '/assets/img/ForWho/MainTable.png',
    title: 'Main Table Image',
  },
  secondaryImage: {
    src: '/assets/img/ForWho/AboutWallets.png',
    title: 'About Wallets Image',
  },
};

const CARD_ITEMS = [
  {
    id: 'card_1',
    imageSrc: '/assets/img/ForWho/Ruby.png',
    imageAltText: 'Ruby 1',
    text: 'for_who.card_text_1',
  },
  {
    id: 'card_2',
    imageSrc: '/assets/img/ForWho/Ruby.png',
    imageAltText: 'Ruby 2',
    text: 'for_who.card_text_2',
  },
];

const styles = {
  mainBox: {
    margin: '89px auto 0 auto',
    padding: '56px 0 206px 0',
    backgroundColor: '#FBFBFB',
  },
  secondaryBoxContainer: {
    maxWidth: '1192px',
    margin: '0 auto',
    height: '100%',
    position: 'relative',
  },
  mainGrid: {
    height: '100%',
    width: '100%',
    padding: '58px 34px 0 34px',
    display: 'flex',
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
};

export default function ForWhoSection() {
  const [cardItems,] = useState(CARD_ITEMS);
  const { isSmallest, isMobile, isSmallTablet } = useScreenSize();
  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  const cardItemsJSX = useMemo(() => cardItems.map((cardItem) => (
        <ForWhoSectionCardItem
          key={cardItem.id}
          imageSrc={cardItem.imageSrc}
          imageAltText={cardItem.imageAltText}
          text={cardItem.text}
        />
      )), [cardItems]);

  return (
    <Box sx={{ ...styles.mainBox, marginTop: isMobile || isSmallest ? '0' : '89px' }}>
      <Box sx={{ ...styles.secondaryBoxContainer }}>
        <Grid
          container
          sx={{
            ...styles.mainGrid,
            flexDirection: isSmallTablet || isMobile || isSmallest ? 'column' : 'row',
            alignItems: isSmallTablet ? 'center' : 'stretch',
            paddingTop: isMobile || isSmallest ? '38px' : '58px',
            gap: isSmallTablet ? '59px' : isSmallest ? '0' : 'normal',
          }}
        >
          {/* Main Texts (Top and Bottom) */}
          <ForWhoMainTextsContent onTryItOutButtonClick={handleTryItOutButtonClick} />

          {/* Images Content With SVG Backgrounds and PNG images */}
          <ForWhoImagesContent
            mainImageSrc={images.mainImage.src}
            mainImageTitle={images.mainImage.title}
            secondaryImageTitle={images.secondaryImage.title}
            secondaryImageSrc={images.secondaryImage.src}
          />
        </Grid>

        {/* Card Items */}
        {isMobile || isSmallest ? (
          <ForWhoSectionCardsMobile cardItemsJSX={cardItemsJSX} />
        ) : (
          <ForWhoSectionCards cardItemsJSX={cardItemsJSX} />
        )}
      </Box>
    </Box>
  );
}
