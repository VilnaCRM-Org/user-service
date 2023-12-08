import { Box, Grid } from '@mui/material';
import { useMemo, useState } from 'react';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import scrollToRegistrationSection from '../../../utils/helpers/scrollToRegistrationSection';
import ForWhoImagesContent from '../ForWhoImagesContent/ForWhoImagesContent';
import ForWhoMainTextsContent from '../ForWhoMainTextsContent/ForWhoMainTextsContent';
import ForWhoSectionCardItem from '../ForWhoSectionCardItem/ForWhoSectionCardItem';
import ForWhoSectionCards from '../ForWhoSectionCards/ForWhoSectionCards';
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
    text: 'for_who.card_text_title',
  },
  {
    id: 'card_2',
    imageSrc: '/assets/img/ForWho/Ruby.png',
    imageAltText: 'Ruby 2',
    text: 'for_who.card_text_business',
  },
];

const styles = {
  mainBox: {
    margin: '0 auto 0 auto',
    padding: '56px 0 206px 0',
    backgroundColor: '#FBFBFB',
    height: '100%',
    maxHeight: '44.875rem', // 718px
    overflow: 'visible',
  },
  mainBoxLaptopOrLower: {
    padding: '0 34px 206px 34px',
  },
  mainBoxMobileOrLower: {
    paddingTop: '0',
    marginTop: '0',
  },
  secondaryBoxContainer: {
    maxWidth: '1192px',
    margin: '0 auto',
    height: '100%',
    position: 'relative',
  },
  mainGrid: {
    height: '39.8125rem', // 637px
    width: '100%',
    maxWidth: '74.5rem', // 1192px
    padding: '0 0 0 0',
    display: 'flex',
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  mainGridMobileOrSmaller: {
    padding: '32px 42px 38px 15px',
  },
};

export default function ForWhoSection() {
  const [cardItems] = useState(CARD_ITEMS);
  const { isSmallest, isMobile, isLaptop, isTablet } = useScreenSize();
  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  const cardItemsJSX = useMemo(
    () =>
      cardItems.map((cardItem) => (
        <ForWhoSectionCardItem
          key={cardItem.id}
          imageSrc={cardItem.imageSrc}
          imageAltText={cardItem.imageAltText}
          text={cardItem.text}
        />
      )),
    [cardItems]
  );

  return (
    <Box
      sx={{
        ...styles.mainBox,
        ...(isLaptop || isTablet ? styles.mainBoxLaptopOrLower : {}),
        ...(isMobile || isSmallest ? styles.mainBoxMobileOrLower : {}),
      }}
    >
      <Box sx={{ ...styles.secondaryBoxContainer }}>
        <Grid
          container
          sx={{
            ...styles.mainGrid,
            ...(isMobile || isSmallest ? styles.mainGridMobileOrSmaller : {}),
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
