import { Box, Container, MobileStepper } from '@mui/material';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import SwipeableViews from 'react-swipeable-views';

import { Button } from '@/components/ui/Button/Button';
import WhyWeSectionCardItem from '@/features/landing/components/WhyWeSection/WhyWeSectionCardItem/WhyWeSectionCardItem';
import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';
import { scrollToRegistrationSection } from '@/features/landing/utils/helpers/scrollToRegistrationSection';

// TODO: Change Carousel to a newer one
export default function WhyWeSectionSlider({ cardItems }: { cardItems: IWhyWeCardItem[] }) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const [activeStep, setActiveStep] = useState(0);
  const [, setCurrentActiveItem] = useState(cardItems[0]);
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
        <SwipeableViews
          axis="x"
          index={activeStep}
          onChangeIndex={handleStepChange}
          enableMouseEvents
        >
          {cardItems.map((item) => (
            <Box
              key={item.id}
              sx={{
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                height: '100%',
              }}
            >
              <WhyWeSectionCardItem
                cardItem={item}
                isSmall
                style={{
                  height: '100%',
                  overflow: 'hidden',
                  margin: '0 5px',
                  padding: '16px 18px 72px 16px',
                  maxHeight: '263px',
                }}
              />
            </Box>
          ))}
        </SwipeableViews>

        <MobileStepper
          variant="dots"
          steps={maxSteps}
          position="static"
          activeStep={activeStep}
          nextButton={null}
          backButton={null}
          sx={{
            display: 'flex',
            justifyContent: 'center',
            marginTop: '25px',
            '& .MuiMobileStepper-dot:not(:last-child)': {
              marginRight: '24px',
            },
          }}
        />
        <Box sx={{ display: 'flex', justifyContent: 'center', marginBottom: '32px' }}>
          <Button
            customVariant="light-blue"
            onClick={handleTryItOutButtonClick}
            style={{ marginTop: '24px' }}
          >
            {t('why_we.button.try_it_out')}
          </Button>
        </Box>
      </Container>
    </Box>
  );
}
