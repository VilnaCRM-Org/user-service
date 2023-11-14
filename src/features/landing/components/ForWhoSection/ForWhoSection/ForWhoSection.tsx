import { useMemo, useState } from 'react';
import { Box, Container, Grid } from '@mui/material';
import {
  scrollToRegistrationSection,
} from '@/features/landing/utils/helpers/scrollToRegistrationSection';
import {
  ForWhoMainTextsContent,
} from '@/features/landing/components/ForWhoSection/ForWhoMainTextsContent/ForWhoMainTextsContent';
import {
  ForWhoImagesContent,
} from '@/features/landing/components/ForWhoSection/ForWhoImagesContent/ForWhoImagesContent';
import {
  ForWhoSectionCardItem,
} from '@/features/landing/components/ForWhoSection/ForWhoSectionCardItem/ForWhoSectionCardItem';

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
    text: 'A private entrepreneur is a psychologist, tutor or dropshipper',
  },
  {
    id: 'card_2',
    imageSrc: '/assets/img/ForWho/Ruby.png',
    imageAltText: 'Ruby 2',
    text: 'medium-scale local project - online courses, design studio or small outsourcing',
  },
];

const styles = {
  mainBox: {
    margin: '89px auto 0 auto',
    padding: '56px 0 206px 0',
    backgroundColor: '#FBFBFB',
  },
  mainContainer: {
    maxWidth: '1192px',
    margin: '0 auto',
    height: '100%',
    position: 'relative',
  },
  mainGrid: {
    height: '100%',
    width: '100%',
  },
};

export function ForWhoSection() {
  const [cardItems, setCardItems] = useState(CARD_ITEMS);
  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  const cardItemsJSX = useMemo(() => {
    return cardItems.map((cardItem) => {
      return <ForWhoSectionCardItem key={cardItem.id} imageSrc={cardItem.imageSrc}
                                    imageAltText={cardItem.imageAltText} text={cardItem.text} />;
    });
  }, [cardItems]);

  return (
    <Box
      sx={{ ...styles.mainBox }}>
      <Container sx={{ ...styles.mainContainer }}>
        <Grid container sx={{ ...styles.mainGrid }}>
          {/* Main Texts (Top and Bottom) */}
          <ForWhoMainTextsContent onTryItOutButtonClick={handleTryItOutButtonClick} />

          {/* Images Content With SVG Backgrounds and PNG images */}
          <ForWhoImagesContent
            mainImageSrc={images.mainImage.src}
            mainImageTitle={images.mainImage.title}
            secondaryImageTitle={images.secondaryImage.title}
            secondaryImageSrc={images.secondaryImage.src} />
        </Grid>
        <Grid container alignItems={'stretch'} spacing={3}
              sx={{
                position: 'absolute',
                bottom: '-150px',
                zIndex: 900,
              }}>
          {[...cardItemsJSX]}
        </Grid>
      </Container>
    </Box>
  );
}
