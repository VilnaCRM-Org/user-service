import { useState } from 'react';
import { Box, Container, MobileStepper } from '@mui/material';
import { useTranslation } from 'react-i18next';
import SwipeableViews from 'react-swipeable-views';
import { autoPlay } from 'react-swipeable-views-utils';

import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';
import { WhyWeSectionCardItem } from '@/features/landing/components/WhyWeSection/WhyWeSectionCardItem/WhyWeSectionCardItem';
import { Button } from '@/components/ui/Button/Button';
import { scrollToRegistrationSection } from '@/features/landing/utils/helpers/scrollToRegistrationSection';

const AutoPlaySwipeableViews = autoPlay(SwipeableViews);

// TODO: Change Carousel to a newer one
export function WhyWeSectionSlider({ cardItems }: { cardItems: IWhyWeCardItem[] }) {
  const { t } = useTranslation();
  const [activeStep, setActiveStep] = useState(0);
  const [currentActiveItem, setCurrentActiveItem] = useState(cardItems[0]);
  const maxSteps = cardItems.length;

  const handleStepChange = (step: number) => {
    setCurrentActiveItem(cardItems[step]);
    setActiveStep(step);
  };

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  return (
    <Box sx={{ padding: '0 15px 0 15px' }}>
      <Container>
        <AutoPlaySwipeableViews
          axis="x"
          index={activeStep}
          onChangeIndex={handleStepChange}
          enableMouseEvents
        >
          {cardItems.map((item, index) => (
            <Box
              key={index}
              sx={{
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                height: '100%',
              }}
            >
              <WhyWeSectionCardItem
                cardItem={item}
                style={{
                  height: '100%',
                  overflow: 'hidden',
                  margin: '0 5px',
                }}
              />
            </Box>
          ))}
        </AutoPlaySwipeableViews>

        <MobileStepper
          steps={maxSteps}
          position="static"
          activeStep={activeStep}
          nextButton={null}
          backButton={null}
          sx={{ display: 'flex', justifyContent: 'center', marginTop: '25px' }}
        />
        <Box sx={{ display: 'flex', justifyContent: 'center' }}>
          <Button
            customVariant={'light-blue'}
            onClick={handleTryItOutButtonClick}
            style={{ marginTop: '24px' }}
          >
            {t('Try it out')}
          </Button>
        </Box>
      </Container>
    </Box>
  );
}
