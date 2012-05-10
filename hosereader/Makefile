CC = cc
CPPFLAGS = -g -O0 -Wall
LDFLAGS = -lcurl libhiredis.a

all: hosereader

hosereader: hosereader.o
	$(CC) $< $(CPPFLAGS) $(LDFLAGS) -o "hosereader"
	
clean:
	$(RM) hosereader *.o *.mod
